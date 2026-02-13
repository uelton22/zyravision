import 'package:json_annotation/json_annotation.dart';

part 'user_model.g.dart';

@JsonSerializable()
class UserModel {
  @JsonKey(fromJson: _idFromJson)
  final String id;
  final String name;
  final String email;
  @JsonKey(name: 'tenant_id')
  final String? tenantId;
  @JsonKey(name: 'avatar_url')
  final String? avatarUrl;
  @JsonKey(defaultValue: [])
  final List<String> roles;
  @JsonKey(defaultValue: [])
  final List<String> permissions;

  const UserModel({
    required this.id,
    required this.name,
    required this.email,
    this.tenantId,
    this.avatarUrl,
    this.roles = const [],
    this.permissions = const [],
  });

  factory UserModel.fromJson(Map<String, dynamic> json) =>
      _$UserModelFromJson(json);

  /// A API pode retornar id como int ou String
  static String _idFromJson(dynamic value) => value.toString();

  Map<String, dynamic> toJson() => _$UserModelToJson(this);
}
