package util;

import model.ModerationReport;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.assertTrue;

class ModerationNoteFormatterTest {

    @Test
    void buildSimpleAiNote_includesSingleLinkThreatFieldForNone() {
        ModerationReport report = new ModerationReport();
        report.setDuplicateScore(0.12);
        report.setToxicity(0.15);
        report.setRelevance(0.72);
        report.setPredictedCategory("Career");
        report.setLinkThreat("NONE");
        report.setLinkThreatTypes("NONE");

        String note = ModerationNoteFormatter.buildSimpleAiNote(report);

        assertTrue(note.contains("LinkThreat=NONE"));
        assertTrue(!note.contains("SafeBrowsingThreatTypes="));
    }

    @Test
    void buildSimpleAiNote_includesSingleLinkThreatFieldWithThreatTypes() {
        ModerationReport report = new ModerationReport();
        report.setDuplicateScore(0.18);
        report.setToxicity(0.80);
        report.setRelevance(0.42);
        report.setPredictedCategory("General");
        report.setLinkThreat("FLAGGED");
        report.setLinkThreatTypes("MALWARE,SOCIAL_ENGINEERING");

        String note = ModerationNoteFormatter.buildSimpleAiNote(report);

        assertTrue(note.contains("LinkThreat=MALWARE,SOCIAL_ENGINEERING"));
        assertTrue(!note.contains("LinkThreat=FLAGGED"));
    }

    @Test
    void buildSimpleAiNote_includesSingleLinkThreatFieldForError() {
        ModerationReport report = new ModerationReport();
        report.setDuplicateScore(0.20);
        report.setToxicity(0.20);
        report.setRelevance(0.65);
        report.setPredictedCategory("General");
        report.setLinkThreat("ERROR");
        report.setLinkThreatTypes("ERROR");

        String note = ModerationNoteFormatter.buildSimpleAiNote(report);

        assertTrue(note.contains("LinkThreat=ERROR"));
    }
}
