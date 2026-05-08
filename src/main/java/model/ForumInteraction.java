package model;

/**
 * FILE ROLE: Forum domain/data model definitions shared across layers.
 * FILE: Forum/src/main/java/model/ForumInteraction.java
 */

/**
 * LOGIC INDEX:
 * - Classes/Enums/Records: ForumInteraction
 * - File logic focus: Domain model and enum logic for forum entities.
 * - Feature coverage: State transport across UI/service/repo layers.
 * - Key methods/blocks: getId, setId, getTargetType, setTargetType, getTargetId, setTargetId, getUserId, setUserId, getInteractionType, setInteractionType
 */
import java.time.LocalDateTime;

/**
 * Data model for forum_interaction rows.
 */
public class ForumInteraction {
    private long id;
    private TargetType targetType;
    private long targetId;
    private int userId;
    private InteractionType interactionType;
    private LocalDateTime createdAt;

    public long getId() { return id; }
    public void setId(long id) { this.id = id; }

    public TargetType getTargetType() { return targetType; }
    public void setTargetType(TargetType targetType) { this.targetType = targetType; }

    public long getTargetId() { return targetId; }
    public void setTargetId(long targetId) { this.targetId = targetId; }

    public int getUserId() { return userId; }
    public void setUserId(int userId) { this.userId = userId; }

    public InteractionType getInteractionType() { return interactionType; }
    public void setInteractionType(InteractionType interactionType) { this.interactionType = interactionType; }

    public LocalDateTime getCreatedAt() { return createdAt; }
    public void setCreatedAt(LocalDateTime createdAt) { this.createdAt = createdAt; }
}
