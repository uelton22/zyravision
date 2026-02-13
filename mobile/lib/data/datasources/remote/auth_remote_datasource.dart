import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/constants.dart';
import '../../../core/network/api_client.dart';
import '../../models/auth_response_model.dart';

final authRemoteDataSourceProvider = Provider<AuthRemoteDataSource>((ref) {
  return AuthRemoteDataSource(ref.read(apiClientProvider));
});

class AuthRemoteDataSource {
  final ApiClient _client;

  AuthRemoteDataSource(this._client);

  Future<AuthResponseModel> login({
    required String email,
    required String password,
    required String deviceName,
  }) async {
    final response = await _client.dio.post(
      ApiPaths.login,
      data: {
        'email': email,
        'password': password,
        'device_name': deviceName,
      },
    );
    return AuthResponseModel.fromJson(response.data as Map<String, dynamic>);
  }

  Future<void> logout() async {
    await _client.dio.post(ApiPaths.logout);
  }

  Future<MeResponseModel> me() async {
    final response = await _client.dio.get(ApiPaths.me);
    return MeResponseModel.fromJson(response.data as Map<String, dynamic>);
  }
}
