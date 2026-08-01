// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'user.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_User _$UserFromJson(Map<String, dynamic> json) => _User(
  id: (json['id'] as num).toInt(),
  name: json['name'] as String,
  email: json['email'] as String,
  whatsapp: json['whatsapp'] as String?,
  role: json['role'] as String,
  roleLabel: json['role_label'] as String,
  isActive: json['is_active'] as bool,
  marketplaceStatus: json['marketplace_status'] as String?,
  expositor: json['expositor'] == null
      ? null
      : ExpositorSummary.fromJson(json['expositor'] as Map<String, dynamic>),
);

Map<String, dynamic> _$UserToJson(_User instance) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'email': instance.email,
  'whatsapp': instance.whatsapp,
  'role': instance.role,
  'role_label': instance.roleLabel,
  'is_active': instance.isActive,
  'marketplace_status': instance.marketplaceStatus,
  'expositor': instance.expositor,
};
