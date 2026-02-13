import '../../domain/entities/user.dart';
import '../models/user_model.dart';

class UserMapper {
  UserMapper._();

  static User toEntity(UserModel model) {
    return User(
      id: model.id,
      name: model.name,
      email: model.email,
      tenantId: model.tenantId,
      avatarUrl: model.avatarUrl,
      roles: model.roles,
      permissions: model.permissions,
    );
  }
}
