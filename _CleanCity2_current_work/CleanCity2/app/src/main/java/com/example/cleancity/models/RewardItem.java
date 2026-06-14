package com.example.cleancity.models;

public class RewardItem {
    private int id;
    private String title;
    private String description;
    private String photo_url;
    private int points_required;
    private String valid_from;
    private String valid_to;

    public int getId() { return id; }
    public String getTitle() { return title; }
    public String getDescription() { return description; }
    public String getPhotoUrl() { return photo_url; }
    public int getPointsRequired() { return points_required; }
    public String getValidFrom() { return valid_from; }
    public String getValidTo() { return valid_to; }
}
