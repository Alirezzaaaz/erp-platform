/// تنظیمات API
class ApiConfig {
  /// آدرس پیش‌فرض Backend
  /// در تولید، این مقدار از فایل config خوانده می‌شود
  static const String defaultBaseUrl = 'http://localhost:8000/api';

  /// خواندن از متغیر محیطی (در Build)
  static String get baseUrl {
    const envUrl = String.fromEnvironment('API_BASE_URL');
    return envUrl.isNotEmpty ? envUrl : defaultBaseUrl;
  }
}
