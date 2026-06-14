package com.example.cleancity.ui;

import android.content.Context;
import android.graphics.Canvas;
import android.graphics.Path;
import android.graphics.RectF;
import android.util.AttributeSet;

import androidx.annotation.Nullable;
import androidx.appcompat.widget.AppCompatImageView;

public class CircleImageView extends AppCompatImageView {
    private final Path clipPath = new Path();
    private final RectF rect = new RectF();

    public CircleImageView(Context context) {
        super(context);
        init();
    }

    public CircleImageView(Context context, @Nullable AttributeSet attrs) {
        super(context, attrs);
        init();
    }

    public CircleImageView(Context context, @Nullable AttributeSet attrs, int defStyleAttr) {
        super(context, attrs, defStyleAttr);
        init();
    }

    private void init() {
        setScaleType(ScaleType.CENTER_CROP);
    }

    @Override
    protected void onDraw(Canvas canvas) {
        int saveCount = canvas.save();
        clipPath.reset();
        rect.set(0, 0, getWidth(), getHeight());
        clipPath.addOval(rect, Path.Direction.CW);
        canvas.clipPath(clipPath);
        super.onDraw(canvas);
        canvas.restoreToCount(saveCount);
    }
}
