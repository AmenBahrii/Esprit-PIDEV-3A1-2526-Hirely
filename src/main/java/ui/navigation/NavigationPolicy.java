package ui.navigation;

/**
 * FILE ROLE: JavaFX route/navigation policy logic for forum windows.
 * FILE: Forum/src/main/java/ui/navigation/NavigationPolicy.java
 */

/**
 * LOGIC INDEX:
 * - Classes/Enums/Records: NavigationPolicy, UserRouteKind
 * - File logic focus: JavaFX controller logic (FXML events, UI state, bindings, async UI updates).
 * - Feature coverage: CRUD flows, search/sort/filter, dialogs, theme/window controls, JavaFX event handling.
 * - Key methods/blocks: No explicit methods (constants/enum/data-holder).
 */
/**
 * Pure navigation rules used by NavigationManager.
 * Kept UI-independent so behavior can be smoke-tested without JavaFX.
 */
final class NavigationPolicy {

    enum UserRouteKind {
        FORUM,
        PROFILE,
        DETAILS
    }

    private NavigationPolicy() {
    }

    static boolean shouldPushCurrentOnOpenDetails(UserRouteKind current) {
        return current != UserRouteKind.DETAILS;
    }

    static String titleForUserRoute(UserRouteKind route) {
        if (route == null) {
            return "Hirely Forum";
        }
        return switch (route) {
            case FORUM -> "Hirely Forum";
            case PROFILE -> "Hirely Profile";
            case DETAILS -> "Hirely Post Details";
        };
    }
}
