package util;

/**
 * FILE ROLE: Shared utility and integration logic used by forum features.
 * FILE: Forum/src/main/java/util/SafeBrowsingClient.java
 */

/**
 * LOGIC INDEX:
 * - Classes/Enums/Records: SafeBrowsingClient, LinkThreat, ScanResult
 * - File logic focus: Utility/integration logic (validation, API clients, session, helpers).
 * - Feature coverage: External API handling, validation/normalization, shared support logic.
 * - Key methods/blocks: linkThreat, threatTypes, scanText, scanTextDetailed, scanUrls, scanUrlsDetailed, buildPayload
 */
import com.google.gson.JsonArray;
import com.google.gson.JsonObject;
import com.google.gson.JsonParser;

import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.time.Duration;
import java.util.ArrayList;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Locale;
import java.util.Set;

/**
 * Google Safe Browsing v4 client for URL threat checks.
 */
public class SafeBrowsingClient {
    public enum LinkThreat {
        NONE,
        FLAGGED,
        ERROR
    }

    private static final String ENDPOINT =
            "https://safebrowsing.googleapis.com/v4/threatMatches:find?key=";
    private static final int MAX_URLS = 500;

    private final HttpClient client;
    private final String apiKey;
    private final int timeoutMs;

    public static final class ScanResult {
        private final LinkThreat linkThreat;
        private final String threatTypes;

        private ScanResult(LinkThreat linkThreat, String threatTypes) {
            this.linkThreat = linkThreat == null ? LinkThreat.ERROR : linkThreat;
            this.threatTypes = (threatTypes == null || threatTypes.isBlank()) ? "NONE" : threatTypes;
        }

        public LinkThreat linkThreat() {
            return linkThreat;
        }

        public String threatTypes() {
            return threatTypes;
        }
    }

    public SafeBrowsingClient() {
        this(Secrets.SAFE_BROWSING_API_KEY, Secrets.SAFE_BROWSING_TIMEOUT_MS);
    }

    SafeBrowsingClient(String apiKey, int timeoutMs) {
        this(
                apiKey,
                timeoutMs,
                HttpClient.newBuilder()
                        .connectTimeout(Duration.ofMillis(Math.max(1000, timeoutMs)))
                        .build());
    }

    SafeBrowsingClient(String apiKey, int timeoutMs, HttpClient client) {
        this.apiKey = apiKey == null ? "" : apiKey.trim();
        this.timeoutMs = timeoutMs > 0 ? timeoutMs : 8000;
        this.client = client;
    }

    public LinkThreat scanText(String... textParts) {
        List<String> urls = UrlExtractionUtil.extractUrls(textParts);
        return scanUrlsDetailed(urls).linkThreat();
    }

    public ScanResult scanTextDetailed(String... textParts) {
        List<String> urls = UrlExtractionUtil.extractUrls(textParts);
        return scanUrlsDetailed(urls);
    }

    public LinkThreat scanUrls(List<String> urls) {
        return scanUrlsDetailed(urls).linkThreat();
    }

    public ScanResult scanUrlsDetailed(List<String> urls) {
        if (urls == null || urls.isEmpty()) {
            return new ScanResult(LinkThreat.NONE, "NONE");
        }
        if (apiKey.isBlank()) {
            DebugLog.error("SafeBrowsing", "SAFE_BROWSING_API_KEY is missing.", null);
            return new ScanResult(LinkThreat.ERROR, "ERROR");
        }
        try {
            String payload = buildPayload(urls);
            HttpRequest request = HttpRequest.newBuilder(URI.create(ENDPOINT + apiKey))
                    .timeout(Duration.ofMillis(timeoutMs))
                    .header("Content-Type", "application/json")
                    .header("User-Agent", "HirelyForum/1.0 (JavaFX)")
                    .POST(HttpRequest.BodyPublishers.ofString(payload))
                    .build();

            HttpResponse<String> response = client.send(request, HttpResponse.BodyHandlers.ofString());
            if (response.statusCode() < 200 || response.statusCode() >= 300) {
                DebugLog.error("SafeBrowsing",
                        "Safe Browsing request failed with status " + response.statusCode(), null);
                return new ScanResult(LinkThreat.ERROR, "ERROR");
            }
            String body = response.body();
            LinkThreat linkThreat = parseThreatResponse(body);
            String threatTypes = parseThreatTypes(body);
            return new ScanResult(linkThreat, threatTypes);
        } catch (Exception ex) {
            DebugLog.error("SafeBrowsing", "Safe Browsing call failed.", ex);
            return new ScanResult(LinkThreat.ERROR, "ERROR");
        }
    }

    static LinkThreat parseThreatResponse(String responseBody) {
        if (responseBody == null || responseBody.isBlank()) {
            return LinkThreat.NONE;
        }
        JsonObject root = JsonParser.parseString(responseBody).getAsJsonObject();
        JsonArray matches = root.has("matches") && root.get("matches").isJsonArray()
                ? root.getAsJsonArray("matches")
                : null;
        if (matches != null && matches.size() > 0) {
            return LinkThreat.FLAGGED;
        }
        return LinkThreat.NONE;
    }

    static String parseThreatTypes(String responseBody) {
        if (responseBody == null || responseBody.isBlank()) {
            return "NONE";
        }
        JsonObject root = JsonParser.parseString(responseBody).getAsJsonObject();
        JsonArray matches = root.has("matches") && root.get("matches").isJsonArray()
                ? root.getAsJsonArray("matches")
                : null;
        if (matches == null || matches.isEmpty()) {
            return "NONE";
        }

        Set<String> unique = new LinkedHashSet<>();
        for (int i = 0; i < matches.size(); i++) {
            if (!matches.get(i).isJsonObject()) {
                continue;
            }
            JsonObject match = matches.get(i).getAsJsonObject();
            if (!match.has("threatType")) {
                continue;
            }
            String threatType = match.get("threatType").isJsonNull() ? "" : match.get("threatType").getAsString();
            String normalized = threatType == null ? "" : threatType.trim().toUpperCase(Locale.ROOT);
            if (!normalized.isBlank()) {
                unique.add(normalized);
            }
        }
        if (unique.isEmpty()) {
            return "NONE";
        }
        return String.join(",", new ArrayList<>(unique));
    }

    private String buildPayload(List<String> urls) {
        JsonObject root = new JsonObject();

        JsonObject clientObj = new JsonObject();
        clientObj.addProperty("clientId", "hirely-forum");
        clientObj.addProperty("clientVersion", "1.0.0");
        root.add("client", clientObj);

        JsonObject threatInfo = new JsonObject();
        JsonArray threatTypes = new JsonArray();
        threatTypes.add("MALWARE");
        threatTypes.add("SOCIAL_ENGINEERING");
        threatTypes.add("UNWANTED_SOFTWARE");
        threatInfo.add("threatTypes", threatTypes);

        JsonArray platformTypes = new JsonArray();
        platformTypes.add("ANY_PLATFORM");
        threatInfo.add("platformTypes", platformTypes);

        JsonArray threatEntryTypes = new JsonArray();
        threatEntryTypes.add("URL");
        threatInfo.add("threatEntryTypes", threatEntryTypes);

        JsonArray threatEntries = new JsonArray();
        int count = 0;
        for (String url : urls) {
            if (url == null || url.isBlank()) {
                continue;
            }
            JsonObject entry = new JsonObject();
            entry.addProperty("url", url);
            threatEntries.add(entry);
            count++;
            if (count >= MAX_URLS) {
                break;
            }
        }
        threatInfo.add("threatEntries", threatEntries);

        root.add("threatInfo", threatInfo);
        return root.toString();
    }
}
