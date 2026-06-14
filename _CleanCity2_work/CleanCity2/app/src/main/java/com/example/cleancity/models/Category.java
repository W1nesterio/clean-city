package com.example.cleancity.models;

public class Category {
    private int id;
    private String name;
    private String icon;
    private boolean active;

    public int getId() {
        return id;
    }

    public String getName() {
        return name;
    }

    public String getIcon() {
        return icon;
    }

    public boolean isActive() {
        return active;
    }

    @Override
    public String toString() {
        return name != null ? name : "Категория #" + id;
    }
}
