package com.example.cleancity.models;

import com.google.gson.annotations.SerializedName;

public class WorkerRegisterRequest {
    private String name;
    private String email;
    private String password;

    @SerializedName("registration_code")
    private String registrationCode;

    public WorkerRegisterRequest(String name, String email, String password, String registrationCode) {
        this.name = name;
        this.email = email;
        this.password = password;
        this.registrationCode = registrationCode;
    }
}
