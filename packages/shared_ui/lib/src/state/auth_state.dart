import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../api/api_client.dart';
import '../models/user.dart';
import '../models/tenant.dart';
import '../models/theme_config.dart';

class AuthState extends ChangeNotifier {
  final ApiClient api;

  User? _user;
  Tenant? _tenant;
  ThemeConfig? _theme;
  bool _isAuthenticated = false;
  bool _isLoading = false;
  String? _error;

  AuthState({required this.api});

  User? get user => _user;
  Tenant? get tenant => _tenant;
  ThemeConfig? get theme => _theme;
  bool get isAuthenticated => _isAuthenticated;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<bool> login({
    required String subdomain,
    required String email,
    required String password,
    required String platform,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await api.post(
        '/v1/auth/login',
        body: {
          'subdomain': subdomain,
          'email': email,
          'password': password,
          'platform': platform,
        },
        withAuth: false,
      );

      if (response['success'] == false) {
        _error = response['message'] ?? 'خطای ورود';
        _isLoading = false;
        notifyListeners();
        return false;
      }

      final token = response['access_token'] as String;
      _user = User.fromJson(response['user']);
      _tenant = Tenant.fromJson(response['tenant']);

      api.setToken(token);
      api.setPlatform(platform);

      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('auth_token', token);
      await prefs.setString('platform', platform);

      await _loadTheme();

      _isAuthenticated = true;
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> _loadTheme() async {
    try {
      final response = await api.get('/v1/theme');
      if (response['success'] == true) {
        _theme = ThemeConfig.fromJson(response['data']);
      }
    } catch (_) {
      _theme = ThemeConfig.defaultConfig();
    }
  }

  Future<bool> tryRestoreSession() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');
    final platform = prefs.getString('platform');

    if (token == null) return false;

    api.setToken(token);
    if (platform != null) api.setPlatform(platform);

    try {
      final response = await api.get('/v1/auth/me');
      if (response['success'] == true) {
        _user = User.fromJson(response['data']['user']);
        _tenant = Tenant.fromJson(response['data']['tenant']);
        await _loadTheme();
        _isAuthenticated = true;
        notifyListeners();
        return true;
      }
    } catch (_) {
      await logout();
    }
    return false;
  }

  Future<void> logout() async {
    try {
      await api.post('/v1/auth/logout');
    } catch (_) {}

    _user = null;
    _tenant = null;
    _theme = null;
    _isAuthenticated = false;
    api.setToken(null);

    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('platform');

    notifyListeners();
  }
}
