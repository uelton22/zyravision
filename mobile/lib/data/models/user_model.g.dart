// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'user_model.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

UserModel _$UserModelFromJson(Map<String, dynamic> json) => UserModel(
  id: UserModel._idFromJson(json['id']),
  name: json['name'] as String,
  email: json['email'] as String,
  tenantId: json['tenant_id'] as String?,
  avatarUrl: json['avatar_url'] as String?,
  roles:
      (json['roles'] as List<dynamic>?)?.map((e) => e as String).toList() ?? [],
  permissions:
      (json['permissions'] as List<dynamic>?)
          ?.map((e) => e as String)
          .toList() ??
      [],
);

Map<String, dynamic> _$UserModelToJson(UserModel instance) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'email': instance.email,
  'tenant_id': instance.tenantId,
  'avatar_url': instance.avatarUrl,
  'roles': instance.roles,
  'permissions': instance.permissions,
};
