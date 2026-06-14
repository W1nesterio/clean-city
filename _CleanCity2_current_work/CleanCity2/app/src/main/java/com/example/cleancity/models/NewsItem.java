package com.example.cleancity.models;

import java.util.List;

public class NewsItem {
    private int id;
    private String title;
    private String body;
    private String published_date;
    private Integer organization_id;
    private List<NewsPhoto> photos;

    public int getId() { return id; }
    public String getTitle() { return title; }
    public String getBody() { return body; }
    public String getPublishedDate() { return published_date; }
    public Integer getOrganizationId() { return organization_id; }
    public List<NewsPhoto> getPhotos() { return photos; }
}
