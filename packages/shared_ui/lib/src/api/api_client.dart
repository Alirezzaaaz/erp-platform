import 'dart:convert';
import 'package:http/http.dart' as http;
import 'api_exception.dart';

class ApiClient {
  final String baseUrl;
  final http.Client _client;

  String? _token;
  String? _platform;

  ApiClient({
    required this.baseUrl,
    http.Client? client,
  }) : _client = client ?? http.Client();

  void setToken(String? token) => _token = token;
  void setPlatform(String platform) => _platform = platform;
  String? get token => _token;

  Map<String, String> _headers({bool withAuth = true}) {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (withAuth && _token != null) {
      headers['Authorization'] = 'Bearer $_token';
    }
    if (_platform != null) {
      headers['X-Platform'] = _platform!;
    }
    return headers;
  }

  Future<Map<String, dynamic>> get(String path, {Map<String, dynamic>? query}) async {
    final uri = _buildUri(path, query);
    final response = await _client.get(uri, headers: _headers());
    return _handle(response);
  }

  Future<Map<String, dynamic>> post(
    String path, {
    Map<String, dynamic>? body,
    bool withAuth = true,
  }) async {
    final uri = _buildUri(path);
    final response = await _client.post(
      uri,
      headers: _headers(withAuth: withAuth),
      body: body != null ? jsonEncode(body) : null,
    );
    return _handle(response);
  }

  Future<Map<String, dynamic>> put(String path, {Map<String, dynamic>? body}) async {
    final uri = _buildUri(path);
    final response = await _client.put(
      uri,
      headers: _headers(),
      body: body != null ? jsonEncode(body) : null,
    );
    return _handle(response);
  }

  Future<Map<String, dynamic>> patch(String path, {Map<String, dynamic>? body}) async {
    final uri = _buildUri(path);
    final response = await _client.patch(
      uri,
      headers: _headers(),
      body: body != null ? jsonEncode(body) : null,
    );
    return _handle(response);
  }

  Future<Map<String, dynamic>> delete(String path) async {
    final uri = _buildUri(path);
    final response = await _client.delete(uri, headers: _headers());
    return _handle(response);
  }

  Uri _buildUri(String path, [Map<String, dynamic>? query]) {
    final fullUrl = '$baseUrl$path';
    final uri = Uri.parse(fullUrl);
    if (query == null || query.isEmpty) return uri;
    return uri.replace(queryParameters: {
      ...uri.queryParameters,
      ...query.map((k, v) => MapEntry(k, v?.toString() ?? '')),
    });
  }

  Map<String, dynamic> _handle(http.Response response) {
    final body = response.body.isEmpty
        ? <String, dynamic>{}
        : jsonDecode(response.body) as Map<String, dynamic>;

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return body;
    }

    throw ApiException(
      message: body['message'] ?? 'خطای نامشخص',
      statusCode: response.statusCode,
      errors: body['errors'] as Map<String, dynamic>?,
    );
  }

  void dispose() => _client.close();
}
