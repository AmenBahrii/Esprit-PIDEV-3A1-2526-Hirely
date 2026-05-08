package util;

/**
 * FILE ROLE: Shared utility and integration logic used by forum features.
 * FILE: Forum/src/main/java/util/LikeToggleGuard.java
 */

/**
 * LOGIC INDEX:
 * - Classes/Enums/Records: LikeToggleGuard
 * - File logic focus: Utility/integration logic (validation, API clients, session, helpers).
 * - Feature coverage: External API handling, validation/normalization, shared support logic.
 * - Key methods/blocks: tryAcquire, release, isInFlight
 */
import java.util.Set;
import java.util.concurrent.ConcurrentHashMap;

/**
 * Lightweight concurrency gate that prevents overlapping like toggles for the same target id.
 */
public final class LikeToggleGuard {
    private final Set<Long> inFlight = ConcurrentHashMap.newKeySet();

    public boolean tryAcquire(long targetId) {
        return inFlight.add(targetId);
    }

    public void release(long targetId) {
        inFlight.remove(targetId);
    }

    public boolean isInFlight(long targetId) {
        return inFlight.contains(targetId);
    }
}
