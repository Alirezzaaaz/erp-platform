class ApiConfig {
  static const String defaultBaseUrl = 'http://localhost:8000/api';

  static String get baseUrl {
    const envUrl = String.fromEnvironment('API_BASE_URL');
    return envUrl.isNotEmpty ? envUrl : defaultBaseUrl;
  }
}
