package com.example.cleancity.models;

import com.google.gson.annotations.SerializedName;

import java.util.List;

public class Ticket {
    private int id;

    @SerializedName("user_id")
    private int userId;

    @SerializedName("category_id")
    private int categoryId;

    private String status;
    private String priority;
    private String lat;
    private String lng;

    @SerializedName("address_text")
    private String addressText;

    private String description;

    @SerializedName("created_at")
    private String createdAt;

    private Category category;
    private List<TicketPhoto> photos;

    public int getId() {
        return id;
    }

    public int getUserId() {
        return userId;
    }

    public int getCategoryId() {
        return categoryId;
    }

    public String getStatus() {
        return status;
    }

    public String getPriority() {
        return priority;
    }

    public String getLat() {
        return lat;
    }

    public String getLng() {
        return lng;
    }

    public String getAddressText() {
        return addressText;
    }

    public String getDescription() {
        return description;
    }

    public String getCreatedAt() {
        return createdAt;
    }

    public Category getCategory() {
        return category;
    }

    public List<TicketPhoto> getPhotos() {
        return photos;
    }

    public String getStatusLabel() {
        if (status == null) return "Неизвестно";

        switch (status) {
            case "created": return "Создана";
            case "assigned": return "Назначена";
            case "accepted": return "Принята";
            case "in_progress": return "В работе";
            case "completed": return "Выполнена";
            case "rejected": return "Отклонена";
            case "duplicate": return "Дубликат";
            case "problem": return "Проблема";
            case "postponed": return "Отложена";
            default: return status;
        }
    }
}
