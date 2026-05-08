package util;

import org.junit.jupiter.api.Test;

import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.CountDownLatch;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.Future;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertFalse;
import static org.junit.jupiter.api.Assertions.assertTrue;

class LikeToggleGuardTest {

    @Test
    void preventsRapidDoubleClickUntilReleased() {
        LikeToggleGuard guard = new LikeToggleGuard();
        long postId = 42L;

        assertTrue(guard.tryAcquire(postId));
        assertFalse(guard.tryAcquire(postId));

        guard.release(postId);
        assertTrue(guard.tryAcquire(postId));
    }

    @Test
    void allowsOnlyOneConcurrentAcquireForSameTarget() throws Exception {
        LikeToggleGuard guard = new LikeToggleGuard();
        long postId = 77L;
        int workers = 16;

        ExecutorService pool = Executors.newFixedThreadPool(workers);
        CountDownLatch ready = new CountDownLatch(workers);
        CountDownLatch start = new CountDownLatch(1);
        List<Future<Boolean>> futures = new ArrayList<>();

        try {
            for (int i = 0; i < workers; i++) {
                futures.add(pool.submit(() -> {
                    ready.countDown();
                    start.await();
                    return guard.tryAcquire(postId);
                }));
            }
            ready.await();
            start.countDown();

            int successCount = 0;
            for (Future<Boolean> future : futures) {
                if (future.get()) {
                    successCount++;
                }
            }
            assertEquals(1, successCount);
            assertTrue(guard.isInFlight(postId));
        } finally {
            pool.shutdownNow();
        }
    }
}
