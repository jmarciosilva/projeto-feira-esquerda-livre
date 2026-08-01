// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'user.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// dart format off
T _$identity<T>(T value) => value;

/// @nodoc
mixin _$User {

 int get id; String get name; String get email; String? get whatsapp; String get role;@JsonKey(name: 'role_label') String get roleLabel;@JsonKey(name: 'is_active') bool get isActive;@JsonKey(name: 'marketplace_status') String? get marketplaceStatus; ExpositorSummary? get expositor;
/// Create a copy of User
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$UserCopyWith<User> get copyWith => _$UserCopyWithImpl<User>(this as User, _$identity);

  /// Serializes this User to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is User&&(identical(other.id, id) || other.id == id)&&(identical(other.name, name) || other.name == name)&&(identical(other.email, email) || other.email == email)&&(identical(other.whatsapp, whatsapp) || other.whatsapp == whatsapp)&&(identical(other.role, role) || other.role == role)&&(identical(other.roleLabel, roleLabel) || other.roleLabel == roleLabel)&&(identical(other.isActive, isActive) || other.isActive == isActive)&&(identical(other.marketplaceStatus, marketplaceStatus) || other.marketplaceStatus == marketplaceStatus)&&(identical(other.expositor, expositor) || other.expositor == expositor));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,name,email,whatsapp,role,roleLabel,isActive,marketplaceStatus,expositor);

@override
String toString() {
  return 'User(id: $id, name: $name, email: $email, whatsapp: $whatsapp, role: $role, roleLabel: $roleLabel, isActive: $isActive, marketplaceStatus: $marketplaceStatus, expositor: $expositor)';
}


}

/// @nodoc
abstract mixin class $UserCopyWith<$Res>  {
  factory $UserCopyWith(User value, $Res Function(User) _then) = _$UserCopyWithImpl;
@useResult
$Res call({
 int id, String name, String email, String? whatsapp, String role,@JsonKey(name: 'role_label') String roleLabel,@JsonKey(name: 'is_active') bool isActive,@JsonKey(name: 'marketplace_status') String? marketplaceStatus, ExpositorSummary? expositor
});


$ExpositorSummaryCopyWith<$Res>? get expositor;

}
/// @nodoc
class _$UserCopyWithImpl<$Res>
    implements $UserCopyWith<$Res> {
  _$UserCopyWithImpl(this._self, this._then);

  final User _self;
  final $Res Function(User) _then;

/// Create a copy of User
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? id = null,Object? name = null,Object? email = null,Object? whatsapp = freezed,Object? role = null,Object? roleLabel = null,Object? isActive = null,Object? marketplaceStatus = freezed,Object? expositor = freezed,}) {
  return _then(_self.copyWith(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as int,name: null == name ? _self.name : name // ignore: cast_nullable_to_non_nullable
as String,email: null == email ? _self.email : email // ignore: cast_nullable_to_non_nullable
as String,whatsapp: freezed == whatsapp ? _self.whatsapp : whatsapp // ignore: cast_nullable_to_non_nullable
as String?,role: null == role ? _self.role : role // ignore: cast_nullable_to_non_nullable
as String,roleLabel: null == roleLabel ? _self.roleLabel : roleLabel // ignore: cast_nullable_to_non_nullable
as String,isActive: null == isActive ? _self.isActive : isActive // ignore: cast_nullable_to_non_nullable
as bool,marketplaceStatus: freezed == marketplaceStatus ? _self.marketplaceStatus : marketplaceStatus // ignore: cast_nullable_to_non_nullable
as String?,expositor: freezed == expositor ? _self.expositor : expositor // ignore: cast_nullable_to_non_nullable
as ExpositorSummary?,
  ));
}
/// Create a copy of User
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$ExpositorSummaryCopyWith<$Res>? get expositor {
    if (_self.expositor == null) {
    return null;
  }

  return $ExpositorSummaryCopyWith<$Res>(_self.expositor!, (value) {
    return _then(_self.copyWith(expositor: value));
  });
}
}


/// Adds pattern-matching-related methods to [User].
extension UserPatterns on User {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _User value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _User() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _User value)  $default,){
final _that = this;
switch (_that) {
case _User():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _User value)?  $default,){
final _that = this;
switch (_that) {
case _User() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( int id,  String name,  String email,  String? whatsapp,  String role, @JsonKey(name: 'role_label')  String roleLabel, @JsonKey(name: 'is_active')  bool isActive, @JsonKey(name: 'marketplace_status')  String? marketplaceStatus,  ExpositorSummary? expositor)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _User() when $default != null:
return $default(_that.id,_that.name,_that.email,_that.whatsapp,_that.role,_that.roleLabel,_that.isActive,_that.marketplaceStatus,_that.expositor);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( int id,  String name,  String email,  String? whatsapp,  String role, @JsonKey(name: 'role_label')  String roleLabel, @JsonKey(name: 'is_active')  bool isActive, @JsonKey(name: 'marketplace_status')  String? marketplaceStatus,  ExpositorSummary? expositor)  $default,) {final _that = this;
switch (_that) {
case _User():
return $default(_that.id,_that.name,_that.email,_that.whatsapp,_that.role,_that.roleLabel,_that.isActive,_that.marketplaceStatus,_that.expositor);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( int id,  String name,  String email,  String? whatsapp,  String role, @JsonKey(name: 'role_label')  String roleLabel, @JsonKey(name: 'is_active')  bool isActive, @JsonKey(name: 'marketplace_status')  String? marketplaceStatus,  ExpositorSummary? expositor)?  $default,) {final _that = this;
switch (_that) {
case _User() when $default != null:
return $default(_that.id,_that.name,_that.email,_that.whatsapp,_that.role,_that.roleLabel,_that.isActive,_that.marketplaceStatus,_that.expositor);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _User extends User {
  const _User({required this.id, required this.name, required this.email, this.whatsapp, required this.role, @JsonKey(name: 'role_label') required this.roleLabel, @JsonKey(name: 'is_active') required this.isActive, @JsonKey(name: 'marketplace_status') this.marketplaceStatus, this.expositor}): super._();
  factory _User.fromJson(Map<String, dynamic> json) => _$UserFromJson(json);

@override final  int id;
@override final  String name;
@override final  String email;
@override final  String? whatsapp;
@override final  String role;
@override@JsonKey(name: 'role_label') final  String roleLabel;
@override@JsonKey(name: 'is_active') final  bool isActive;
@override@JsonKey(name: 'marketplace_status') final  String? marketplaceStatus;
@override final  ExpositorSummary? expositor;

/// Create a copy of User
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$UserCopyWith<_User> get copyWith => __$UserCopyWithImpl<_User>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$UserToJson(this, );
}

@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is _User&&(identical(other.id, id) || other.id == id)&&(identical(other.name, name) || other.name == name)&&(identical(other.email, email) || other.email == email)&&(identical(other.whatsapp, whatsapp) || other.whatsapp == whatsapp)&&(identical(other.role, role) || other.role == role)&&(identical(other.roleLabel, roleLabel) || other.roleLabel == roleLabel)&&(identical(other.isActive, isActive) || other.isActive == isActive)&&(identical(other.marketplaceStatus, marketplaceStatus) || other.marketplaceStatus == marketplaceStatus)&&(identical(other.expositor, expositor) || other.expositor == expositor));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,name,email,whatsapp,role,roleLabel,isActive,marketplaceStatus,expositor);

@override
String toString() {
  return 'User(id: $id, name: $name, email: $email, whatsapp: $whatsapp, role: $role, roleLabel: $roleLabel, isActive: $isActive, marketplaceStatus: $marketplaceStatus, expositor: $expositor)';
}


}

/// @nodoc
abstract mixin class _$UserCopyWith<$Res> implements $UserCopyWith<$Res> {
  factory _$UserCopyWith(_User value, $Res Function(_User) _then) = __$UserCopyWithImpl;
@override @useResult
$Res call({
 int id, String name, String email, String? whatsapp, String role,@JsonKey(name: 'role_label') String roleLabel,@JsonKey(name: 'is_active') bool isActive,@JsonKey(name: 'marketplace_status') String? marketplaceStatus, ExpositorSummary? expositor
});


@override $ExpositorSummaryCopyWith<$Res>? get expositor;

}
/// @nodoc
class __$UserCopyWithImpl<$Res>
    implements _$UserCopyWith<$Res> {
  __$UserCopyWithImpl(this._self, this._then);

  final _User _self;
  final $Res Function(_User) _then;

/// Create a copy of User
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? id = null,Object? name = null,Object? email = null,Object? whatsapp = freezed,Object? role = null,Object? roleLabel = null,Object? isActive = null,Object? marketplaceStatus = freezed,Object? expositor = freezed,}) {
  return _then(_User(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as int,name: null == name ? _self.name : name // ignore: cast_nullable_to_non_nullable
as String,email: null == email ? _self.email : email // ignore: cast_nullable_to_non_nullable
as String,whatsapp: freezed == whatsapp ? _self.whatsapp : whatsapp // ignore: cast_nullable_to_non_nullable
as String?,role: null == role ? _self.role : role // ignore: cast_nullable_to_non_nullable
as String,roleLabel: null == roleLabel ? _self.roleLabel : roleLabel // ignore: cast_nullable_to_non_nullable
as String,isActive: null == isActive ? _self.isActive : isActive // ignore: cast_nullable_to_non_nullable
as bool,marketplaceStatus: freezed == marketplaceStatus ? _self.marketplaceStatus : marketplaceStatus // ignore: cast_nullable_to_non_nullable
as String?,expositor: freezed == expositor ? _self.expositor : expositor // ignore: cast_nullable_to_non_nullable
as ExpositorSummary?,
  ));
}

/// Create a copy of User
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$ExpositorSummaryCopyWith<$Res>? get expositor {
    if (_self.expositor == null) {
    return null;
  }

  return $ExpositorSummaryCopyWith<$Res>(_self.expositor!, (value) {
    return _then(_self.copyWith(expositor: value));
  });
}
}

// dart format on
