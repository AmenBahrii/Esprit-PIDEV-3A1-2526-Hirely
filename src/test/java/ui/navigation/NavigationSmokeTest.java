package ui.navigation;

import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertFalse;
import static org.junit.jupiter.api.Assertions.assertTrue;

class NavigationSmokeTest {

    @Test
    void openDetailsPushRule_preventsDetailsToDetailsStackGrowth() {
        assertFalse(NavigationPolicy.shouldPushCurrentOnOpenDetails(NavigationPolicy.UserRouteKind.DETAILS));
        assertTrue(NavigationPolicy.shouldPushCurrentOnOpenDetails(NavigationPolicy.UserRouteKind.FORUM));
        assertTrue(NavigationPolicy.shouldPushCurrentOnOpenDetails(NavigationPolicy.UserRouteKind.PROFILE));
        assertTrue(NavigationPolicy.shouldPushCurrentOnOpenDetails(null));
    }

    @Test
    void routeTitles_matchExpectedLabelsForGrading() {
        assertEquals("Hirely Forum", NavigationPolicy.titleForUserRoute(NavigationPolicy.UserRouteKind.FORUM));
        assertEquals("Hirely Profile", NavigationPolicy.titleForUserRoute(NavigationPolicy.UserRouteKind.PROFILE));
        assertEquals("Hirely Post Details", NavigationPolicy.titleForUserRoute(NavigationPolicy.UserRouteKind.DETAILS));
        assertEquals("Hirely Forum", NavigationPolicy.titleForUserRoute(null));
    }
}
