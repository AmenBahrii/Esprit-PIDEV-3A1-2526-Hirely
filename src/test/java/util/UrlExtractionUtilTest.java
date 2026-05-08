package util;

import org.junit.jupiter.api.Test;

import java.util.List;

import static org.junit.jupiter.api.Assertions.assertEquals;

class UrlExtractionUtilTest {

    @Test
    void extractUrls_handlesHttpHttpsWww_andDeduplicates() {
        String title = "Read https://example.com/jobs and www.hirely.dev/careers";
        String content = "Mirror: https://example.com/jobs and https://safe.test/path.";

        List<String> urls = UrlExtractionUtil.extractUrls(title, content);

        assertEquals(
                List.of(
                        "https://example.com/jobs",
                        "http://www.hirely.dev/careers",
                        "https://safe.test/path"),
                urls);
    }

    @Test
    void extractUrls_handlesBareDomains_protocolRelative_mixedCase_andPunctuation() {
        String text = "Try 1337x.to, //1337x.to/top?q=1#x, (www.1337x.to), and HTTPS://1337X.TO:443/path.";

        List<String> urls = UrlExtractionUtil.extractUrls(text);

        assertEquals(
                List.of(
                        "http://1337x.to",
                        "http://1337x.to/top?q=1#x",
                        "http://www.1337x.to",
                        "HTTPS://1337X.TO:443/path"),
                urls);
    }

    @Test
    void extractUrls_ignoresEmailDomains_andKeepsRealDomainMentions() {
        String text = "Email me at user@1337x.to but block this: [1337x.to]";

        List<String> urls = UrlExtractionUtil.extractUrls(text);

        assertEquals(List.of("http://1337x.to"), urls);
    }
}
