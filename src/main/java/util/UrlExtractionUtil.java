package util;

/**
 * FILE ROLE: Shared utility and integration logic used by forum features.
 * FILE: Forum/src/main/java/util/UrlExtractionUtil.java
 */

/**
 * LOGIC INDEX:
 * - Classes/Enums/Records: UrlExtractionUtil
 * - File logic focus: Utility/integration logic (validation, API clients, session, helpers).
 * - Feature coverage: External API handling, validation/normalization, shared support logic.
 * - Key methods/blocks: extractUrls, trimTrailingPunctuation, trimLeadingPunctuation, hasScheme, looksLikeBareDomain, isLeadingNoise, isTrailingNoise
 */
import java.util.ArrayList;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Locale;
import java.util.Set;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Extracts and normalizes URLs from free-form text.
 */
public final class UrlExtractionUtil {
    private static final Pattern URL_PATTERN = Pattern.compile(
            "(?i)(?<![a-z0-9@._-])(?:https?://|//|www\\.|(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\\.)+[a-z]{2,63})[^\\s<>\"']*");
    private static final Pattern SCHEME_PATTERN = Pattern.compile("(?i)^[a-z][a-z0-9+.-]*://.*$");
    private static final Pattern DOMAIN_PATTERN = Pattern.compile(
            "(?i)^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\\.)+[a-z]{2,63}$");

    private UrlExtractionUtil() {
    }

    public static List<String> extractUrls(String... textParts) {
        Set<String> unique = new LinkedHashSet<>();
        if (textParts == null) {
            return List.of();
        }
        for (String part : textParts) {
            if (part == null || part.isBlank()) {
                continue;
            }
            Matcher matcher = URL_PATTERN.matcher(part);
            while (matcher.find()) {
                String raw = matcher.group();
                String normalized = normalizeUrl(raw);
                if (!normalized.isBlank()) {
                    unique.add(normalized);
                }
            }
        }
        return new ArrayList<>(unique);
    }

    static String normalizeUrl(String url) {
        if (url == null) {
            return "";
        }
        String value = trimLeadingPunctuation(trimTrailingPunctuation(url.trim()));
        if (value.isBlank()) {
            return "";
        }

        String lower = value.toLowerCase(Locale.ROOT);
        if (lower.startsWith("//")) {
            return "http:" + value;
        }
        if (lower.startsWith("www.")) {
            return "http://" + value;
        }
        if (hasScheme(value)) {
            return value;
        }
        if (looksLikeBareDomain(value)) {
            return "http://" + value;
        }
        return "";
    }

    private static String trimTrailingPunctuation(String value) {
        String out = value;
        while (!out.isEmpty() && isTrailingNoise(out.charAt(out.length() - 1))) {
            out = out.substring(0, out.length() - 1);
        }
        return out;
    }

    private static String trimLeadingPunctuation(String value) {
        String out = value;
        while (!out.isEmpty() && isLeadingNoise(out.charAt(0))) {
            out = out.substring(1);
        }
        return out;
    }

    private static boolean hasScheme(String value) {
        return SCHEME_PATTERN.matcher(value).matches();
    }

    private static boolean looksLikeBareDomain(String value) {
        String hostPart = value;
        int slash = hostPart.indexOf('/');
        if (slash >= 0) {
            hostPart = hostPart.substring(0, slash);
        }
        int query = hostPart.indexOf('?');
        if (query >= 0) {
            hostPart = hostPart.substring(0, query);
        }
        int fragment = hostPart.indexOf('#');
        if (fragment >= 0) {
            hostPart = hostPart.substring(0, fragment);
        }
        if (hostPart.isBlank()) {
            return false;
        }
        int colon = hostPart.indexOf(':');
        if (colon >= 0) {
            hostPart = hostPart.substring(0, colon);
        }
        return DOMAIN_PATTERN.matcher(hostPart).matches();
    }

    private static boolean isLeadingNoise(char c) {
        return c == '(' || c == '[' || c == '{' || c == '<' || c == '"' || c == '\'';
    }

    private static boolean isTrailingNoise(char c) {
        return c == '.' || c == ',' || c == ';' || c == ':' || c == '!' || c == '?' || c == ')'
                || c == ']' || c == '}' || c == '>' || c == '"' || c == '\'';
    }
}
