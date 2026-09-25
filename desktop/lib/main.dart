import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:provider/provider.dart';
import 'package:shared_ui/shared_ui.dart';
import 'screens/login_screen.dart';
import 'screens/home_shell.dart';

void main() {
  runApp(const ErpDesktopApp());
}

class ErpDesktopApp extends StatefulWidget {
  const ErpDesktopApp({super.key});

  @override
  State<ErpDesktopApp> createState() => _ErpDesktopAppState();
}

class _ErpDesktopAppState extends State<ErpDesktopApp> {
  late final ApiClient _api;
  late final AuthState _auth;

  @override
  void initState() {
    super.initState();
    const apiUrl = String.fromEnvironment(
      'API_BASE_URL',
      defaultValue: 'http://localhost:8000/api',
    );
    _api = ApiClient(baseUrl: apiUrl);
    _auth = AuthState(api: _api);
    _auth.tryRestoreSession();
  }

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        Provider<ApiClient>.value(value: _api),
        ChangeNotifierProvider<AuthState>.value(value: _auth),
      ],
      child: Consumer<AuthState>(
        builder: (context, auth, _) {
          final themeConfig = auth.theme ?? ThemeConfig.defaultConfig();

          return MaterialApp(
            title: 'سیستم ERP',
            debugShowCheckedModeBanner: false,
            theme: AppTheme.fromConfig(themeConfig),
            locale: const Locale('fa', 'IR'),
            supportedLocales: const [
              Locale('fa', 'IR'),
              Locale('en', 'US'),
            ],
            localizationsDelegates: const [
              GlobalMaterialLocalizations.delegate,
              GlobalWidgetsLocalizations.delegate,
              GlobalCupertinoLocalizations.delegate,
            ],
            builder: (context, child) => Directionality(
              textDirection: TextDirection.rtl,
              child: child ?? const SizedBox.shrink(),
            ),
            home: auth.isAuthenticated ? const HomeShell() : const LoginScreen(),
          );
        },
      ),
    );
  }
}
