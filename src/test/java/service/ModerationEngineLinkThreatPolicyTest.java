package service;

import org.junit.jupiter.api.Test;

import java.util.ArrayList;
import java.util.List;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertTrue;

class ModerationEngineLinkThreatPolicyTest {

    @Test
    void enforceLinkThreatPolicy_flagged_forcesPending() {
        List<String> reasons = new ArrayList<>();

        String decision = ModerationEngine.enforceLinkThreatPolicy("APPROVED", "FLAGGED", reasons);

        assertEquals("PENDING", decision);
        assertTrue(reasons.stream().anyMatch(r -> r.toLowerCase().contains("safe browsing")));
    }

    @Test
    void enforceLinkThreatPolicy_error_forcesPending() {
        List<String> reasons = new ArrayList<>();

        String decision = ModerationEngine.enforceLinkThreatPolicy("APPROVED", "ERROR", reasons);

        assertEquals("PENDING", decision);
        assertTrue(reasons.stream().anyMatch(r -> r.toLowerCase().contains("safe browsing")));
    }
}
