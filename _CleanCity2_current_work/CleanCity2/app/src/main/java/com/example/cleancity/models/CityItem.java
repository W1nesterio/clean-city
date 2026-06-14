package com.example.cleancity.models;

import com.google.gson.annotations.SerializedName;

public class CityItem {
    @SerializedName("id")
    public int id;

    @SerializedName("name")
    public String name;

    @SerializedName("region")
    public String region;

    @Override
    public String toString() {
        return name != null ? name : "";
    }
}
