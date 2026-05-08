package model;

/**
 * FILE ROLE: Forum domain/data model definitions shared across layers.
 * FILE: Forum/src/main/java/model/NotificationType.java
 */

/**
 * LOGIC INDEX:
 * - Classes/Enums/Records: NotificationType
 * - File logic focus: Domain model and enum logic for forum entities.
 * - Feature coverage: State transport across UI/service/repo layers.
 * - Key methods/blocks: No explicit methods (constants/enum/data-holder).
 */
public enum NotificationType {
    POST_LIKED,
    COMMENT_LIKED,
    COMMENT_ADDED,
    POST_COMMENTED,
    POST_STATUS_CHANGED
}
