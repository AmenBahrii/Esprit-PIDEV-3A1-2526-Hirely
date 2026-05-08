package util;

import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.assertEquals;

class SafeBrowsingClientTest {

    @Test
    void parseThreatResponse_emptyObject_returnsNone() {
        assertEquals(SafeBrowsingClient.LinkThreat.NONE, SafeBrowsingClient.parseThreatResponse("{}"));
    }

    @Test
    void parseThreatResponse_matchesPresent_returnsFlagged() {
        String json = "{\"matches\":[{\"threatType\":\"MALWARE\"}]}";
        assertEquals(SafeBrowsingClient.LinkThreat.FLAGGED, SafeBrowsingClient.parseThreatResponse(json));
    }

    @Test
    void parseThreatTypes_matchesPresent_returnsUniqueThreatTypes() {
        String json = """
                {"matches":[
                  {"threatType":"MALWARE"},
                  {"threatType":"SOCIAL_ENGINEERING"},
                  {"threatType":"MALWARE"}
                ]}
                """;
        assertEquals("MALWARE,SOCIAL_ENGINEERING", SafeBrowsingClient.parseThreatTypes(json));
    }

    @Test
    void parseThreatTypes_noMatches_returnsNone() {
        assertEquals("NONE", SafeBrowsingClient.parseThreatTypes("{}"));
    }
}
