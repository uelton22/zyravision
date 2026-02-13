import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/config/app_config.dart';
import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';
import '../../../state/auth/auth_providers.dart';
import '../../router/route_names.dart';
import '../../widgets/buttons/zy_button.dart';
import '../../widgets/inputs/zy_text_field.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _passwordFocusNode = FocusNode();

  bool _obscurePassword = true;
  bool _isLoading = false;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    _passwordFocusNode.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() => _isLoading = true);

    final success = await ref.read(authNotifierProvider.notifier).login(
          email: _emailController.text.trim(),
          password: _passwordController.text,
        );

    if (!mounted) return;
    setState(() => _isLoading = false);

    if (success) {
      context.go(RouteNames.dashboard);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final authState = ref.watch(authNotifierProvider);

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: ZySpacing.xl),
            child: Form(
              key: _formKey,
              child: AutofillGroup(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const SizedBox(height: ZySpacing.xxl),

                    // Logo
                    Container(
                      width: 72,
                      height: 72,
                      decoration: BoxDecoration(
                        color: ZyColors.primary,
                        borderRadius: ZySpacing.borderRadiusXl,
                      ),
                      child: const Icon(
                        Icons.videocam,
                        size: 36,
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(height: ZySpacing.base),

                    Text(
                      'ZyraVision',
                      style: theme.textTheme.displaySmall?.copyWith(
                        fontWeight: FontWeight.w700,
                        color: ZyColors.primary,
                      ),
                    ),
                    const SizedBox(height: ZySpacing.xs),

                    Text(
                      'Monitoramento Inteligente',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.outline,
                      ),
                    ),
                    const SizedBox(height: ZySpacing.xxxl),

                    // Error message
                    if (authState.errorMessage != null) ...[
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(ZySpacing.md),
                        decoration: BoxDecoration(
                          color: ZyColors.dangerLight,
                          borderRadius: ZySpacing.borderRadiusMd,
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.error_outline,
                                size: 18, color: ZyColors.danger),
                            const SizedBox(width: ZySpacing.sm),
                            Expanded(
                              child: Text(
                                authState.errorMessage!,
                                style: theme.textTheme.bodySmall?.copyWith(
                                  color: ZyColors.danger,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: ZySpacing.base),
                    ],

                    // Email
                    ZyTextField(
                      label: 'Email',
                      hint: 'seu@email.com',
                      controller: _emailController,
                      keyboardType: TextInputType.emailAddress,
                      textInputAction: TextInputAction.next,
                      autofillHints: const [AutofillHints.email],
                      prefixIcon: Icons.email_outlined,
                      required: true,
                    ),
                    const SizedBox(height: ZySpacing.base),

                    // Password
                    ZyTextField(
                      label: 'Senha',
                      hint: 'Sua senha',
                      controller: _passwordController,
                      focusNode: _passwordFocusNode,
                      obscureText: _obscurePassword,
                      textInputAction: TextInputAction.done,
                      autofillHints: const [AutofillHints.password],
                      prefixIcon: Icons.lock_outlined,
                      required: true,
                      suffixWidget: IconButton(
                        icon: Icon(
                          _obscurePassword
                              ? Icons.visibility_outlined
                              : Icons.visibility_off_outlined,
                        ),
                        onPressed: () {
                          setState(() => _obscurePassword = !_obscurePassword);
                        },
                      ),
                    ),
                    const SizedBox(height: ZySpacing.xl),

                    // Login button
                    ZyButton(
                      label: 'Entrar',
                      onPressed: _isLoading ? null : _handleLogin,
                      loading: _isLoading,
                      fullWidth: true,
                      size: ZyButtonSize.lg,
                    ),
                    const SizedBox(height: ZySpacing.base),

                    // Forgot password
                    TextButton(
                      onPressed: () {
                        // TODO: Implement forgot password in M5
                      },
                      child: Text(
                        'Esqueceu a senha?',
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: theme.colorScheme.primary,
                        ),
                      ),
                    ),

                    const SizedBox(height: ZySpacing.xxxl),

                    // Version
                    Text(
                      'v${AppConfig.appVersion}',
                      style: theme.textTheme.labelSmall?.copyWith(
                        color: theme.colorScheme.outline,
                      ),
                    ),
                    const SizedBox(height: ZySpacing.base),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
