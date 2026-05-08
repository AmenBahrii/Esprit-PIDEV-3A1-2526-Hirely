package util;

import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;
import com.sun.net.httpserver.HttpServer;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;

import java.io.IOException;
import java.io.OutputStream;
import java.net.InetSocketAddress;

import static org.junit.jupiter.api.Assertions.*;

class GeminiClientTest {

    private HttpServer server;
    private int port;
    private String mockBaseUrl;

    @BeforeEach
    void setup() throws IOException {
        server = HttpServer.create(new InetSocketAddress(0), 0);
        port = server.getAddress().getPort();
        mockBaseUrl = "http://localhost:" + port + "/v1beta/models/";
        server.start();
    }

    @AfterEach
    void teardown() {
        if (server != null) {
            server.stop(0);
        }
    }

    @Test
    void testGeminiKey_returnsValid_on200() {
        server.createContext("/v1beta/models", new HttpHandler() {
            @Override
            public void handle(HttpExchange exchange) throws IOException {
                String response = "{\"models\": [{\"name\": \"models/gemini-pro\"}]}";
                exchange.getResponseHeaders().set("Content-Type", "application/json");
                exchange.sendResponseHeaders(200, response.length());
                try (OutputStream os = exchange.getResponseBody()) {
                    os.write(response.getBytes());
                }
            }
        });

        GeminiClient client = new GeminiClient(mockBaseUrl, "test-key");
        GeminiClient.GeminiHealthStatus status = client.testGeminiKey();

        assertEquals(GeminiClient.HealthStatus.VALID_AUTHENTICATED, status.status());
        assertEquals(200, status.httpCode());
        assertTrue(status.modelList().contains("gemini-pro"));
    }

    @Test
    void testGeminiKey_returnsQuota_on429() {
        server.createContext("/v1beta/models", new HttpHandler() {
            @Override
            public void handle(HttpExchange exchange) throws IOException {
                String response = "{\"error\": {\"code\": 429, \"message\": \"Quota exceeded\"}}";
                exchange.getResponseHeaders().set("Content-Type", "application/json");
                exchange.sendResponseHeaders(429, response.length());
                try (OutputStream os = exchange.getResponseBody()) {
                    os.write(response.getBytes());
                }
            }
        });

        GeminiClient client = new GeminiClient(mockBaseUrl, "test-key");
        GeminiClient.GeminiHealthStatus status = client.testGeminiKey();

        assertEquals(GeminiClient.HealthStatus.QUOTA_EXHAUSTED, status.status());
        assertEquals(429, status.httpCode());
    }

    @Test
    void generateReply_throwsQuota_on429() {
        server.createContext("/v1beta/models/gemini-2.5-pro:generateContent", new HttpHandler() {
            @Override
            public void handle(HttpExchange exchange) throws IOException {
                String response = "{\"error\": {\"code\": 429, \"message\": \"Quota exceeded\"}}";
                exchange.sendResponseHeaders(429, response.length());
                try (OutputStream os = exchange.getResponseBody()) {
                    os.write(response.getBytes());
                }
            }
        });
        // We need to handle other models as well or they will fail with 404 in the
        // client
        // Actually, the client tries all candidates. If they all give 429, it throws
        // QUOTA_EXHAUSTED.

        GeminiClient client = new GeminiClient(mockBaseUrl, "test-key");
        // We override the candidate list or just mock all of them
        // For simplicity, let's just test that it eventually reports quota if we mock
        // the first one as 429
        // and let others fail or also be 429.

        // The client currently expects specific candidate names.

        String reply = client.generateReply("Title", "Content", "Comment", "Tag");
        // In the client, catch block returns RATE_LIMIT_FALLBACK for 429/quota errors
        assertEquals("Gemini quota/rate limit reached right now. Please try again in a moment.", reply);
    }

    @Test
    void generateReply_returnsAuthFallback_on401() {
        server.createContext("/v1beta/models/gemini-2.5-pro:generateContent", new HttpHandler() {
            @Override
            public void handle(HttpExchange exchange) throws IOException {
                String response = "{\"error\": {\"code\": 401, \"message\": \"Unauthorized\"}}";
                exchange.sendResponseHeaders(401, response.length());
                try (OutputStream os = exchange.getResponseBody()) {
                    os.write(response.getBytes());
                }
            }
        });

        GeminiClient client = new GeminiClient(mockBaseUrl, "test-key");
        String reply = client.generateReply("Title", "Content", "Comment", "Tag");
        assertEquals("Gemini API key is invalid or not authorized for this project.", reply);
    }
}
