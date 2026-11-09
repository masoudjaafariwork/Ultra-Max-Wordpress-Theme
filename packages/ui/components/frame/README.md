# Frame

قاب صفحه (CORE-07): تمام‌عرض یا جعبه‌ای، پیوند پرش به محتوا و ناحیه main.

```php
Frame::open( array( 'boxed' => true ) )->print(); /* … */ Frame::close()->print();
```

| ورودی | نوع | پیش‌فرض | توضیح |
| --- | --- | --- | --- |
| `boxed` | بولی | `false` | |`n`n`Frame::body_classes()`، `Frame::skip_link()`، `Frame::main_open()` و `Frame::main_close()` هم هست.

`class` در همه اجزا فقط کلاس چیدمان می‌پذیرد.
