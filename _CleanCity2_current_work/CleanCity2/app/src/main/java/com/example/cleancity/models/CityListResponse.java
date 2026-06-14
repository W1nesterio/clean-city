package com.example.cleancity.models;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class CityListResponse {
    @SerializedName("cities")
    public List<CityItem> cities;
}
