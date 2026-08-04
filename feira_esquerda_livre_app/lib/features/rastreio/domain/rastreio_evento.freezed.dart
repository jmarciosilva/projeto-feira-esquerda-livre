// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'rastreio_evento.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// dart format off
T _$identity<T>(T value) => value;

/// @nodoc
mixin _$RastreioEvento {

 String get status; String get description; String? get location;@JsonKey(name: 'happened_at') DateTime? get happenedAt;
/// Create a copy of RastreioEvento
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$RastreioEventoCopyWith<RastreioEvento> get copyWith => _$RastreioEventoCopyWithImpl<RastreioEvento>(this as RastreioEvento, _$identity);

  /// Serializes this RastreioEvento to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is RastreioEvento&&(identical(other.status, status) || other.status == status)&&(identical(other.description, description) || other.description == description)&&(identical(other.location, location) || other.location == location)&&(identical(other.happenedAt, happenedAt) || other.happenedAt == happenedAt));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,status,description,location,happenedAt);

@override
String toString() {
  return 'RastreioEvento(status: $status, description: $description, location: $location, happenedAt: $happenedAt)';
}


}

/// @nodoc
abstract mixin class $RastreioEventoCopyWith<$Res>  {
  factory $RastreioEventoCopyWith(RastreioEvento value, $Res Function(RastreioEvento) _then) = _$RastreioEventoCopyWithImpl;
@useResult
$Res call({
 String status, String description, String? location,@JsonKey(name: 'happened_at') DateTime? happenedAt
});




}
/// @nodoc
class _$RastreioEventoCopyWithImpl<$Res>
    implements $RastreioEventoCopyWith<$Res> {
  _$RastreioEventoCopyWithImpl(this._self, this._then);

  final RastreioEvento _self;
  final $Res Function(RastreioEvento) _then;

/// Create a copy of RastreioEvento
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? status = null,Object? description = null,Object? location = freezed,Object? happenedAt = freezed,}) {
  return _then(_self.copyWith(
status: null == status ? _self.status : status // ignore: cast_nullable_to_non_nullable
as String,description: null == description ? _self.description : description // ignore: cast_nullable_to_non_nullable
as String,location: freezed == location ? _self.location : location // ignore: cast_nullable_to_non_nullable
as String?,happenedAt: freezed == happenedAt ? _self.happenedAt : happenedAt // ignore: cast_nullable_to_non_nullable
as DateTime?,
  ));
}

}


/// Adds pattern-matching-related methods to [RastreioEvento].
extension RastreioEventoPatterns on RastreioEvento {
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

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _RastreioEvento value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _RastreioEvento() when $default != null:
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

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _RastreioEvento value)  $default,){
final _that = this;
switch (_that) {
case _RastreioEvento():
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

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _RastreioEvento value)?  $default,){
final _that = this;
switch (_that) {
case _RastreioEvento() when $default != null:
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

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String status,  String description,  String? location, @JsonKey(name: 'happened_at')  DateTime? happenedAt)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _RastreioEvento() when $default != null:
return $default(_that.status,_that.description,_that.location,_that.happenedAt);case _:
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

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String status,  String description,  String? location, @JsonKey(name: 'happened_at')  DateTime? happenedAt)  $default,) {final _that = this;
switch (_that) {
case _RastreioEvento():
return $default(_that.status,_that.description,_that.location,_that.happenedAt);case _:
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

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String status,  String description,  String? location, @JsonKey(name: 'happened_at')  DateTime? happenedAt)?  $default,) {final _that = this;
switch (_that) {
case _RastreioEvento() when $default != null:
return $default(_that.status,_that.description,_that.location,_that.happenedAt);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _RastreioEvento implements RastreioEvento {
  const _RastreioEvento({required this.status, required this.description, this.location, @JsonKey(name: 'happened_at') this.happenedAt});
  factory _RastreioEvento.fromJson(Map<String, dynamic> json) => _$RastreioEventoFromJson(json);

@override final  String status;
@override final  String description;
@override final  String? location;
@override@JsonKey(name: 'happened_at') final  DateTime? happenedAt;

/// Create a copy of RastreioEvento
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$RastreioEventoCopyWith<_RastreioEvento> get copyWith => __$RastreioEventoCopyWithImpl<_RastreioEvento>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$RastreioEventoToJson(this, );
}

@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is _RastreioEvento&&(identical(other.status, status) || other.status == status)&&(identical(other.description, description) || other.description == description)&&(identical(other.location, location) || other.location == location)&&(identical(other.happenedAt, happenedAt) || other.happenedAt == happenedAt));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,status,description,location,happenedAt);

@override
String toString() {
  return 'RastreioEvento(status: $status, description: $description, location: $location, happenedAt: $happenedAt)';
}


}

/// @nodoc
abstract mixin class _$RastreioEventoCopyWith<$Res> implements $RastreioEventoCopyWith<$Res> {
  factory _$RastreioEventoCopyWith(_RastreioEvento value, $Res Function(_RastreioEvento) _then) = __$RastreioEventoCopyWithImpl;
@override @useResult
$Res call({
 String status, String description, String? location,@JsonKey(name: 'happened_at') DateTime? happenedAt
});




}
/// @nodoc
class __$RastreioEventoCopyWithImpl<$Res>
    implements _$RastreioEventoCopyWith<$Res> {
  __$RastreioEventoCopyWithImpl(this._self, this._then);

  final _RastreioEvento _self;
  final $Res Function(_RastreioEvento) _then;

/// Create a copy of RastreioEvento
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? status = null,Object? description = null,Object? location = freezed,Object? happenedAt = freezed,}) {
  return _then(_RastreioEvento(
status: null == status ? _self.status : status // ignore: cast_nullable_to_non_nullable
as String,description: null == description ? _self.description : description // ignore: cast_nullable_to_non_nullable
as String,location: freezed == location ? _self.location : location // ignore: cast_nullable_to_non_nullable
as String?,happenedAt: freezed == happenedAt ? _self.happenedAt : happenedAt // ignore: cast_nullable_to_non_nullable
as DateTime?,
  ));
}


}

// dart format on
