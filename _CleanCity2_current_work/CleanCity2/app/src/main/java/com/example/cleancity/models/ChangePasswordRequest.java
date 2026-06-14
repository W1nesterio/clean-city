package com.example.cleancity.models;

import com.google.gson.annotations.SerializedName;

public class ChangePasswordRequest {
    @SerializedName("current_password")
    public String currentPassword;

    @SerializedName("new_password")
    public String newPassword;

    @SerializedName("new_password_confirmation")
    public String newPasswordConfirmation;

    public ChangePasswordRequest(String currentPassword, String newPassword) {
        this.currentPassword = currentPassword;
        this.newPassword = newPassword;
        this.newPasswordConfirmation = newPassword;
    }
}
