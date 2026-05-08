package util;

import model.ForumPost;
import org.junit.jupiter.api.Test;
import repo.ForumPostRepository;

import java.util.ArrayList;
import java.util.List;

/**
 * Dev-only smoke check against Google Safe Browsing.
 * Run manually when verifying key wiring and URL detection.
 */
class SafeBrowsingDevThreatSmokeTest {

    private static final String MALWARE_TEST_URL = "https://testsafebrowsing.appspot.com/s/malware.html";
    private static final String PHISHING_TEST_URL = "https://testsafebrowsing.appspot.com/s/phishing.html";

    @Test
    void runThreatLinkSmokeCheck() throws Exception {
        SafeBrowsingClient client = new SafeBrowsingClient();

        List<String> directUrls = List.of(MALWARE_TEST_URL, PHISHING_TEST_URL);
        for (String url : directUrls) {
            printScanResult(client, "URL", url, List.of(url));
        }

        ForumPostRepository postRepo = new ForumPostRepository();
        for (long postId : List.of(28L, 30L)) {
            ForumPost post = postRepo.findById(postId);
            if (post == null) {
                System.out.println("POST #" + postId + ": NOT FOUND");
                continue;
            }
            List<String> urls = UrlExtractionUtil.extractUrls(post.getTitle(), post.getContent());
            if (urls.isEmpty()) {
                System.out.println("POST #" + postId + ": NO URLS");
                continue;
            }
            printScanResult(client, "POST #" + postId, summarizePost(post), new ArrayList<>(urls));
        }
    }

    private void printScanResult(SafeBrowsingClient client, String source, String label, List<String> urls) {
        SafeBrowsingClient.ScanResult result = client.scanUrlsDetailed(urls);
        if (result.linkThreat() == SafeBrowsingClient.LinkThreat.FLAGGED) {
            System.out.println(source + " | THREAT DETECTED: " + label + " | types=" + result.threatTypes());
            return;
        }
        if (result.linkThreat() == SafeBrowsingClient.LinkThreat.NONE) {
            System.out.println(source + " | NO THREAT: " + label);
            return;
        }
        System.out.println(source + " | THREAT CHECK ERROR: " + label + " | types=" + result.threatTypes());
    }

    private String summarizePost(ForumPost post) {
        String title = post == null || post.getTitle() == null ? "" : post.getTitle().trim();
        if (title.length() > 80) {
            title = title.substring(0, 80) + "...";
        }
        return title.isBlank() ? "Post Content" : title;
    }
}
