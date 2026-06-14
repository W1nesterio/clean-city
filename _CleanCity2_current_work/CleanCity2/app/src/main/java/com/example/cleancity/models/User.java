package com.example.cleancity.models;

import com.google.gson.annotations.SerializedName;

public class User {
    private int id;
    private String name;
    private String email;
    private String role;
    private String phone;
    private String position;

    @SerializedName("avatar_url")
    private String avatarUrl;

    @SerializedName("organization_id")
    private Integer organizationId;

    private Organization organization;

    public int getId() {
        return id;
    }

    public String getName() {
        return name;
    }

    public String getEmail() {
        return email;
    }

    public String getRole() {
        return role;
    }

    public String getPhone() {
        return phone;
    }

    public String getPosition() {
        return position;
    }

    public String getAvatarUrl() {
        return avatarUrl;
    }

    public Integer getOrganizationId() {
        return organizationId;
    }

    public Organization getOrganization() {
        return organization;
    }
}
