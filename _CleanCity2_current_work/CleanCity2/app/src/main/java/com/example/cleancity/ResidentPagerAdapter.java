package com.example.cleancity;

import androidx.annotation.NonNull;
import androidx.fragment.app.Fragment;
import androidx.fragment.app.FragmentActivity;
import androidx.viewpager2.adapter.FragmentStateAdapter;

import com.example.cleancity.resident.CreateTicketFragment;
import com.example.cleancity.resident.HomeResidentFragment;
import com.example.cleancity.resident.ResidentAvailableTasksFragment;
import com.example.cleancity.resident.SettingsResidentFragment;
import com.example.cleancity.resident.TicketHistoryFragment;

public class ResidentPagerAdapter extends FragmentStateAdapter {

    public ResidentPagerAdapter(@NonNull FragmentActivity activity) {
        super(activity);
    }

    @NonNull
    @Override
    public Fragment createFragment(int position) {
        switch (position) {
            case 1: return new CreateTicketFragment();
            case 2: return new TicketHistoryFragment();
            case 3: return new ResidentAvailableTasksFragment();
            case 4: return new SettingsResidentFragment();
            default: return new HomeResidentFragment();
        }
    }

    @Override
    public int getItemCount() {
        return 5;
    }
}
