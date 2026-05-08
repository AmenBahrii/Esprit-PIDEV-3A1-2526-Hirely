package ui.navigation;

/**
 * FILE ROLE: JavaFX route/navigation policy logic for forum windows.
 * FILE: Forum/src/main/java/ui/navigation/NavigationManager.java
 */

/**
 * LOGIC INDEX:
 * - Classes/Enums/Records: NavigationManager, UserRoute
 * - File logic focus: JavaFX controller logic (FXML events, UI state, bindings, async UI updates).
 * - Feature coverage: CRUD flows, search/sort/filter, dialogs, theme/window controls, JavaFX event handling.
 * - Key methods/blocks: registerPrimaryStage, registerUserStage, setPrimaryRootLoader, openMainHomeRoot, openForumHomeRoot, openForumPostDetailsById, reapplyPrimaryForumStyles, openUserForumRoot, openUserProfile, openUserPostDetails
 */
import javafx.application.Platform;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.Alert;
import javafx.scene.layout.StackPane;
import javafx.stage.Stage;
import javafx.stage.StageStyle;
import model.ForumPost;
import repo.ForumPostRepository;
import ui.AdminForumController;
import ui.PostDetailsController;
import util.DebugLog;
import util.Session;

import java.util.ArrayDeque;
import java.util.ArrayList;
import java.util.Deque;
import java.util.List;
import java.util.Objects;
import java.util.function.Supplier;

/**
 * Centralized JavaFX navigation for a shared primary stage:
 * - main app home root
 * - forum user roots
 * and one reusable admin stage.
 */
public final class NavigationManager {

    private static final String TAG = "NavigationManager";
    private static final ForumPostRepository POST_REPO = new ForumPostRepository();
    private static final Deque<UserRoute> USER_ROUTE_STACK = new ArrayDeque<>();

    private static Stage userStage;
    private static Scene userScene;
    private static Stage adminStage;
    private static AdminForumController adminController;
    private static UserRoute currentUserRoute;
    private static Supplier<Parent> primaryRootLoader = NavigationManager::loadDefaultMainHomeRoot;
    private static final List<String> primaryBaseStylesheets = new ArrayList<>();

    private NavigationManager() {
    }

    public static synchronized void registerPrimaryStage(Stage stage) {
        registerUserStage(stage);
    }

    public static synchronized void registerUserStage(Stage stage) {
        userStage = Objects.requireNonNull(stage, "stage");
        if (userStage.getScene() == null) {
            userScene = new Scene(new StackPane(), 1400, 850);
            userStage.setScene(userScene);
        } else {
            userScene = userStage.getScene();
        }

        primaryBaseStylesheets.clear();
        primaryBaseStylesheets.addAll(userScene.getStylesheets());

        userStage.setOnCloseRequest(evt -> {
            evt.consume();
            exitApplication();
        });
        DebugLog.info(TAG, "Registered primary stage");
    }

    public static synchronized void setPrimaryRootLoader(Supplier<Parent> loader) {
        primaryRootLoader = Objects.requireNonNull(loader, "loader");
    }

    public static synchronized void openMainHomeRoot() {
        if (userStage == null || userScene == null) {
            DebugLog.error(TAG, "Primary stage is not registered.", null);
            return;
        }
        try {
            Parent root = primaryRootLoader.get();
            USER_ROUTE_STACK.clear();
            currentUserRoute = null;
            userScene.setRoot(root);
            applyPrimaryMainStyles(userScene);
            userStage.setScene(userScene);
            userStage.setTitle("Hirely - Home");
            userStage.show();
            userStage.toFront();
            userStage.requestFocus();
            DebugLog.info(TAG, "Route applied: MAIN_HOME");
        } catch (Exception ex) {
            DebugLog.error(TAG, "Failed opening main home root", ex);
            showError("Failed to open Home", ex);
        }
    }

    public static synchronized void openForumHomeRoot() {
        if (!Session.isAuthenticated()) {
            DebugLog.error(TAG, "Blocked FORUM route: no authenticated session.", null);
            openMainHomeRoot();
            return;
        }
        DebugLog.info(TAG, "Route request: FORUM (reset)");
        USER_ROUTE_STACK.clear();
        navigateToUserRoute(new UserRoute(NavigationPolicy.UserRouteKind.FORUM, 0), false);
    }

    public static synchronized void openForumPostDetailsById(int postId) {
        openUserPostDetailsById(postId);
    }

    public static synchronized void reapplyPrimaryForumStyles() {
        if (userScene == null) {
            return;
        }
        applyPrimaryForumStyles(userScene);
    }

    public static synchronized void openUserForumRoot() {
        openForumHomeRoot();
    }

    public static synchronized void openUserProfile() {
        DebugLog.info(TAG, "Route request: PROFILE");
        navigateToUserRoute(new UserRoute(NavigationPolicy.UserRouteKind.PROFILE, 0), true);
    }

    public static synchronized void openUserPostDetails(ForumPost post) {
        if (post == null) {
            return;
        }
        try {
            openUserPostDetailsById(Math.toIntExact(post.getId()));
        } catch (ArithmeticException ex) {
            showError("Failed to open post", new Exception("Post ID is out of supported range.", ex));
        }
    }

    public static synchronized void openUserPostDetailsById(int postId) {
        if (postId <= 0) {
            return;
        }
        DebugLog.info(TAG, "Route request: DETAILS(postId=" + postId + ")");
        NavigationPolicy.UserRouteKind current = currentUserRoute == null ? null : currentUserRoute.type;
        boolean pushCurrent = NavigationPolicy.shouldPushCurrentOnOpenDetails(current);
        navigateToUserRoute(new UserRoute(NavigationPolicy.UserRouteKind.DETAILS, postId), pushCurrent);
    }

    public static synchronized void goBackUser() {
        if (USER_ROUTE_STACK.isEmpty()) {
            DebugLog.info(TAG, "Back route: stack empty -> FORUM");
            openForumHomeRoot();
            return;
        }
        UserRoute previous = USER_ROUTE_STACK.pop();
        DebugLog.info(TAG, "Back route: -> " + previous);
        navigateToUserRoute(previous, false);
    }

    public static synchronized void openOrFocusAdminWindow() {
        Session.AuthenticatedUser current = Session.getCurrentUser();
        String userId = current == null ? "null" : String.valueOf(current.userId());
        String role = current == null ? "NONE" : current.role().name();
        System.out.println("[SessionFlow] openOrFocusAdminWindow called -> userId=" + userId + " role=" + role);
        if (current == null) {
            DebugLog.error(TAG, "Blocked ADMIN route: no authenticated session.", null);
            openMainHomeRoot();
            return;
        }
        if (!current.isAdmin()) {
            DebugLog.error(TAG, "Blocked ADMIN route: current session is not admin.", null);
            openForumHomeRoot();
            return;
        }
        openAdminForumRoot();
    }

    public static synchronized void openAdminForumRoot() {
        Session.AuthenticatedUser current = Session.getCurrentUser();
        if (current == null) {
            DebugLog.error(TAG, "Blocked ADMIN route: no authenticated session.", null);
            openMainHomeRoot();
            return;
        }
        if (!current.isAdmin()) {
            DebugLog.error(TAG, "Blocked ADMIN route: current session is not admin.", null);
            openForumHomeRoot();
            return;
        }
        System.out.println("[SessionFlow] openAdminForumRoot -> userId=" + current.userId()
                + " role=" + current.role() + " action=OPEN_ADMIN");
        if (userStage == null || userScene == null) {
            DebugLog.error(TAG, "Primary stage is not registered.", null);
            return;
        }
        try {
            FXMLLoader loader = new FXMLLoader(NavigationManager.class.getResource("/forum/ui/AdminForumView.fxml"));
            Parent root = loader.load();
            adminController = loader.getController();
            USER_ROUTE_STACK.clear();
            currentUserRoute = null;
            userScene.setRoot(root);
            applyPrimaryForumStyles(userScene);
            userStage.setScene(userScene);
            userStage.setTitle("Hirely - Admin Panel");
            userStage.show();
            userStage.toFront();
            userStage.requestFocus();
            DebugLog.info(TAG, "Admin panel opened in primary window");
        } catch (Exception ex) {
            showError("Failed to open Admin Panel", ex);
        }
    }

    public static synchronized void focusUserWindow() {
        if (userStage == null) {
            return;
        }
        if (currentUserRoute == null) {
            applyPrimaryMainStyles(userScene);
        } else {
            applyPrimaryForumStyles(userScene);
        }
        if (!userStage.isShowing()) {
            userStage.show();
        }
        userStage.toFront();
        userStage.requestFocus();
        DebugLog.info(TAG, "User window focused");
    }

    public static synchronized void hideAdminWindow() {
        if (adminStage != null && adminStage.isShowing()) {
            adminStage.hide();
            DebugLog.info(TAG, "Admin window hidden");
        }
    }

    public static synchronized void exitApplication() {
        DebugLog.info(TAG, "Application exit requested");
        if (adminStage != null) {
            adminStage.hide();
        }
        if (userStage != null) {
            userStage.hide();
        }
        Platform.exit();
        System.exit(0);
    }

    private static void navigateToUserRoute(UserRoute target, boolean pushCurrent) {
        if (userStage == null || userScene == null) {
            DebugLog.error(TAG, "Primary stage is not registered.", null);
            return;
        }
        try {
            Parent root = loadUserRoot(target);
            if (pushCurrent && currentUserRoute != null && !currentUserRoute.equals(target)) {
                USER_ROUTE_STACK.push(currentUserRoute);
            }
            userScene.setRoot(root);
            applyPrimaryForumStyles(userScene);
            userStage.setScene(userScene);
            userStage.setTitle(NavigationPolicy.titleForUserRoute(target.type));
            userStage.show();
            userStage.toFront();
            userStage.requestFocus();
            currentUserRoute = target;
            DebugLog.info(TAG, "Route applied: " + target + " (stackSize=" + USER_ROUTE_STACK.size() + ")");
        } catch (Exception ex) {
            DebugLog.error(TAG, "Failed opening route " + target, ex);
            showError("Failed to navigate", ex);
        }
    }

    private static Parent loadUserRoot(UserRoute route) throws Exception {
        return switch (route.type) {
            case FORUM -> new FXMLLoader(NavigationManager.class.getResource("/forum/ui/UserForumView.fxml")).load();
            case PROFILE -> new FXMLLoader(NavigationManager.class.getResource("/forum/ui/UserProfileView.fxml")).load();
            case DETAILS -> loadDetailsRoot(route.postId);
        };
    }

    private static Parent loadDetailsRoot(int postId) throws Exception {
        ForumPost post = POST_REPO.findById(postId);
        if (post == null) {
            throw new Exception("Post #" + postId + " is no longer available.");
        }
        FXMLLoader loader = new FXMLLoader(NavigationManager.class.getResource("/forum/ui/PostDetailsView.fxml"));
        Parent root = loader.load();
        PostDetailsController controller = loader.getController();
        controller.setOnBackRequested(NavigationManager::goBackUser);
        controller.setPost(post);
        return root;
    }

    private static Parent loadDefaultMainHomeRoot() {
        try {
            return new FXMLLoader(NavigationManager.class.getResource("/MainShell.fxml")).load();
        } catch (Exception ex) {
            throw new IllegalStateException("Unable to load /MainShell.fxml", ex);
        }
    }

    private static void applyPrimaryMainStyles(Scene scene) {
        if (scene == null) {
            return;
        }
        scene.getStylesheets().clear();
        addPrimaryBaseStyles(scene);
    }

    private static void applyPrimaryForumStyles(Scene scene) {
        if (scene == null) {
            return;
        }
        scene.getStylesheets().clear();
        addPrimaryBaseStyles(scene);
        String forumTheme = NavigationManager.class.getResource(Session.getThemeStylesheetPath()).toExternalForm();
        scene.getStylesheets().add(forumTheme);
    }

    private static void applyAdminTheme(Scene scene) {
        if (scene == null) {
            return;
        }
        scene.getStylesheets().clear();
        scene.getStylesheets().add(NavigationManager.class.getResource(Session.getThemeStylesheetPath()).toExternalForm());
    }

    private static void refreshAdminForReuse() {
        if (adminController == null) {
            return;
        }
        try {
            adminController.refreshForReuse();
        } catch (Exception ex) {
            DebugLog.error(TAG, "Failed refreshing admin window before focus", ex);
        }
    }

    private static void addPrimaryBaseStyles(Scene scene) {
        for (String stylesheet : primaryBaseStylesheets) {
            if (stylesheet == null || stylesheet.isBlank()) {
                continue;
            }
            if (isForumThemeStylesheet(stylesheet)) {
                continue;
            }
            scene.getStylesheets().add(stylesheet);
        }
    }

    private static boolean isForumThemeStylesheet(String stylesheet) {
        return stylesheet.contains("/forum/styles/hirely.css")
                || stylesheet.contains("/forum/styles/hirely-light.css");
    }

    private static void showError(String title, Exception ex) {
        Alert alert = new Alert(Alert.AlertType.ERROR);
        alert.setTitle("Error");
        alert.setHeaderText(title);
        alert.setContentText(ex == null ? "Unknown error" : ex.getMessage());
        alert.showAndWait();
    }

    private static final class UserRoute {
        private final NavigationPolicy.UserRouteKind type;
        private final int postId;

        private UserRoute(NavigationPolicy.UserRouteKind type, int postId) {
            this.type = type;
            this.postId = postId;
        }

        @Override
        public boolean equals(Object obj) {
            if (this == obj) {
                return true;
            }
            if (!(obj instanceof UserRoute other)) {
                return false;
            }
            return type == other.type && postId == other.postId;
        }

        @Override
        public int hashCode() {
            return Objects.hash(type, postId);
        }

        @Override
        public String toString() {
            return type + (type == NavigationPolicy.UserRouteKind.DETAILS ? "(postId=" + postId + ")" : "");
        }
    }
}
