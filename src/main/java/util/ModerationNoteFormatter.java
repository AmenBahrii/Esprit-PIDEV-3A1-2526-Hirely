package util;

/**
 * FILE ROLE: Shared utility and integration logic used by forum features.
 * FILE: Forum/src/main/java/util/ModerationNoteFormatter.java
 */

/**
 * LOGIC INDEX:
 * - Classes/Enums/Records: ModerationNoteFormatter
 * - File logic focus: Utility/integration logic (validation, API clients, session, helpers).
 * - Feature coverage: External API handling, validation/normalization, shared support logic.
 * - Key methods/blocks: buildSimpleAiNote, clamp01, safeCategory, resolveThreatValue
 */
import java.util.Locale;

import model.ModerationReport;

/**
 * Builds a compact, single-line AI moderation note for storage.
 */
public final class ModerationNoteFormatter {

    private static final int MAX_LENGTH = 500;
    private static final String LINK_THREAT_NONE = "NONE";
    private static final String LINK_THREAT_ERROR = "ERROR";

    private ModerationNoteFormatter() {
    }

    public static String buildSimpleAiNote(ModerationReport report) {
        if (report == null) {
            return "";
        }

        double duplicate = clamp01(report.getDuplicateScore());
        double toxicity = clamp01(report.getToxicity());
        double relevance = clamp01(report.getRelevance());

        String category = safeCategory(report.getPredictedCategory());
        boolean hasRelevance = !Double.isNaN(report.getRelevance());
        String linkThreatValue = resolveThreatValue(report.getLinkThreatTypes(), report.getLinkThreat());

        String note;
        if (hasRelevance) {
            note = String.format(
                    Locale.ROOT,
                    "AI: Duplicate=%.2f | Toxicity=%.2f | Relevance=%.2f | LinkThreat=%s (%s)",
                    duplicate,
                    toxicity,
                    relevance,
                    linkThreatValue,
                    category);
        } else {
            note = String.format(
                    Locale.ROOT,
                    "AI: Duplicate=%.2f | Toxicity=%.2f | LinkThreat=%s (%s)",
                    duplicate,
                    toxicity,
                    linkThreatValue,
                    category);
        }

        // sanitize: single line, trimmed, max length
        String sanitized = note.replace('\n', ' ').replace('\r', ' ');
        sanitized = sanitized.replaceAll("\\s{2,}", " ").trim();
        if (sanitized.length() > MAX_LENGTH) {
            sanitized = sanitized.substring(0, MAX_LENGTH);
        }
        return sanitized;
    }

    private static double clamp01(double v) {
        if (Double.isNaN(v)) {
            return 0.0;
        }
        if (v < 0.0) {
            return 0.0;
        }
        if (v > 1.0) {
            return 1.0;
        }
        return v;
    }

    private static String safeCategory(String value) {
        String v = value == null ? "" : value.trim();
        if (v.isBlank()) {
            return "General";
        }
        return v;
    }

    private static String resolveThreatValue(String threatTypes, String linkThreat) {
        String normalizedTypes = threatTypes == null ? "" : threatTypes.trim().toUpperCase(Locale.ROOT);
        if (!normalizedTypes.isBlank() && !LINK_THREAT_NONE.equals(normalizedTypes)) {
            return normalizedTypes;
        }

        String normalizedThreat = linkThreat == null ? "" : linkThreat.trim().toUpperCase(Locale.ROOT);
        if (LINK_THREAT_ERROR.equals(normalizedThreat)) {
            return LINK_THREAT_ERROR;
        }
        return LINK_THREAT_NONE;
    }
}
