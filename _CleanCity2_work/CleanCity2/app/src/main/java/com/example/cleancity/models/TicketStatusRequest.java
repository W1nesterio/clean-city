package com.example.cleancity.models;

public class TicketStatusRequest {
    private String status;
    private String comment;

    public TicketStatusRequest(String status, String comment) {
        this.status = status;
        this.comment = comment;
    }
}
