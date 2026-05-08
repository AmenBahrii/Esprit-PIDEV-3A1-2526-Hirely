package service;

import model.Notification;
import model.NotificationType;
import org.junit.jupiter.api.Test;
import repo.NotificationRepository;
import repo.UserRepository;

import java.sql.Timestamp;
import java.time.Instant;
import java.time.LocalDateTime;
import java.time.ZoneId;
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;
import java.util.Locale;
import java.util.Set;
import java.util.concurrent.ConcurrentHashMap;
import java.util.concurrent.CountDownLatch;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.Future;

import static org.junit.jupiter.api.Assertions.assertEquals;

class NotificationServiceSpamTest {

    @Test
    void repeatedLikeUnlikeLikeFlowDoesNotSpamPostLikeNotification() {
        InMemoryNotificationRepository repo = new InMemoryNotificationRepository();
        NotificationService service = new NotificationService(repo, new StubUserRepository());

        for (int i = 0; i < 8; i++) {
            service.notifyPostLiked(9L, 2, 1);
        }

        assertEquals(1, repo.countMatching(1, 2, NotificationType.POST_LIKED.name(), 9L, null));
    }

    @Test
    void concurrentMultiWindowLikeEventsCreateSingleActiveNotification() throws Exception {
        InMemoryNotificationRepository repo = new InMemoryNotificationRepository();
        NotificationService service = new NotificationService(repo, new StubUserRepository());

        int workers = 24;
        ExecutorService pool = Executors.newFixedThreadPool(workers);
        CountDownLatch ready = new CountDownLatch(workers);
        CountDownLatch start = new CountDownLatch(1);
        List<Future<?>> futures = new ArrayList<>();

        try {
            for (int i = 0; i < workers; i++) {
                futures.add(pool.submit(() -> {
                    ready.countDown();
                    start.await();
                    service.notifyPostLiked(101L, 2, 1);
                    return null;
                }));
            }
            ready.await();
            start.countDown();
            for (Future<?> future : futures) {
                future.get();
            }
        } finally {
            pool.shutdownNow();
        }

        assertEquals(1, repo.countMatching(1, 2, NotificationType.POST_LIKED.name(), 101L, null));
    }

    @Test
    void repeatedCommentLikeEventsDoNotSpamNotifications() {
        InMemoryNotificationRepository repo = new InMemoryNotificationRepository();
        NotificationService service = new NotificationService(repo, new StubUserRepository());

        for (int i = 0; i < 10; i++) {
            service.notifyCommentLiked(9L, 123L, 2, 1);
        }

        assertEquals(1, repo.countMatching(1, 2, NotificationType.COMMENT_LIKED.name(), 9L, 123L));
    }

    private static final class StubUserRepository extends UserRepository {
        @Override
        public String getDisplayNameById(int userId) {
            return "User #" + userId;
        }
    }

    private static final class InMemoryNotificationRepository extends NotificationRepository {
        private final List<Notification> rows = Collections.synchronizedList(new ArrayList<>());
        private final Set<String> activeDedupKeys = ConcurrentHashMap.newKeySet();

        @Override
        public void create(Notification n) {
            if (n == null) {
                return;
            }
            String dedupKey = buildActiveDedupKey(n);
            if (dedupKey != null && !activeDedupKeys.add(dedupKey)) {
                return;
            }
            Notification copy = copyOf(n);
            copy.setCreatedAt(LocalDateTime.now());
            rows.add(copy);
        }

        @Override
        public boolean existsRecentSimilar(int recipientUserId, int actorUserId, String type, Long postId, Long commentId,
                Timestamp since) {
            Instant threshold = since.toInstant();
            synchronized (rows) {
                return rows.stream().anyMatch(n -> matches(n, recipientUserId, actorUserId, type, postId, commentId, threshold));
            }
        }

        int countMatching(int recipientUserId, int actorUserId, String type, Long postId, Long commentId) {
            synchronized (rows) {
                return (int) rows.stream()
                        .filter(n -> n.getRecipientUserId() == recipientUserId)
                        .filter(n -> n.getActorUserId() != null && n.getActorUserId() == actorUserId)
                        .filter(n -> type.equals(n.getType()))
                        .filter(n -> sameNullableLong(n.getPostId(), postId))
                        .filter(n -> sameNullableLong(n.getCommentId(), commentId))
                        .count();
            }
        }

        private boolean matches(Notification n, int recipientUserId, int actorUserId, String type, Long postId, Long commentId,
                Instant threshold) {
            if (n.getRecipientUserId() != recipientUserId) {
                return false;
            }
            if (n.getActorUserId() == null || n.getActorUserId() != actorUserId) {
                return false;
            }
            if (!type.equals(n.getType())) {
                return false;
            }
            if (!sameNullableLong(n.getPostId(), postId) || !sameNullableLong(n.getCommentId(), commentId)) {
                return false;
            }
            if (n.getCreatedAt() == null) {
                return false;
            }
            Instant created = n.getCreatedAt().atZone(ZoneId.systemDefault()).toInstant();
            return !created.isBefore(threshold);
        }

        private static boolean sameNullableLong(Long left, Long right) {
            return left == null ? right == null : left.equals(right);
        }

        private static Notification copyOf(Notification source) {
            Notification n = new Notification();
            n.setRecipientUserId(source.getRecipientUserId());
            n.setActorUserId(source.getActorUserId());
            n.setType(source.getType());
            n.setMessage(source.getMessage());
            n.setPostId(source.getPostId());
            n.setCommentId(source.getCommentId());
            n.setRead(source.isRead());
            return n;
        }

        private static String buildActiveDedupKey(Notification n) {
            if (n.getActorUserId() == null || n.getType() == null || n.isRead()) {
                return null;
            }
            String type = n.getType().trim().toUpperCase(Locale.ROOT);
            if (!NotificationType.POST_LIKED.name().equals(type) && !NotificationType.COMMENT_LIKED.name().equals(type)) {
                return null;
            }
            long postId = n.getPostId() == null ? 0L : n.getPostId();
            long commentId = n.getCommentId() == null ? 0L : n.getCommentId();
            return n.getRecipientUserId() + ":" + n.getActorUserId() + ":" + type + ":" + postId + ":" + commentId;
        }
    }
}
