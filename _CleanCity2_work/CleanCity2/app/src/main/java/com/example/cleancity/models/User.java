package com.example.cleancity.models;

import com.google.gson.annotations.SerializedName;

public class User {
    private int id;
    private String name;
    private String email;
    private String role;

    @SerializedName("organization_id")
    private Integer organizationId;

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

    public Integer getOrganizationId() {
        return organizationId;
    }
}
