// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'rastreio.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// dart format off
T _$identity<T>(T value) => value;

/// @nodoc
mixin _$Rastreio {

 String? get status; String? get carrier;@JsonKey(name: 'service_name') String? get serviceName;@JsonKey(name: 'tracking_code') String? get trackingCode;@JsonKey(name: 'shipped_at') DateTime? get shippedAt;@JsonKey(name: 'delivered_at') DateTime? get deliveredAt;@JsonKey(name: 'estimated_delivery_date') DateTime? get estimatedDeliveryDate;@JsonKey(name: 'carrier_tracking_url') String? get carrierTrackingUrl; Map<String, dynamic>? get expositor; List<RastreioEvento> get events;
/// Create a copy of Rastreio
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$RastreioCopyWith<Rastreio> get copyWith => _$RastreioCopyWithImpl<Rastreio>(this as Rastreio, _$identity);

  /// Serializes this Rastreio to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is Rastreio&&(identical(other.status, status) || other.status == status)&&(identical(other.carrier, carrier) || other.carrier == carrier)&&(identical(other.serviceName, serviceName) || other.serviceName == serviceName)&&(identical(other.trackingCode, trackingCode) || other.trackingCode == trackingCode)&&(identical(other.shippedAt, shippedAt) || other.shippedAt == shippedAt)&&(identical(other.deliveredAt, deliveredAt) || other.deliveredAt == deliveredAt)&&(identical(other.estimatedDeliveryDate, estimatedDeliveryDate) || other.estimatedDeliveryDate == estimatedDeliveryDate)&&(identical(other.carrierTrackingUrl, carrierTrackingUrl) || other.carrierTrackingUrl == carrierTrackingUrl)&&const DeepCollectionEquality().equals(other.expositor, expositor)&&const DeepCollectionEquality().equals(other.events, events));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,status,carrier,serviceName,trackingCode,shippedAt,deliveredAt,estimatedDeliveryDate,carrierTrackingUrl,const DeepCollectionEquality().hash(expositor),const DeepCollectionEquality().hash(events));

@override
String toString() {
  return 'Rastreio(status: $status, carrier: $carrier, serviceName: $serviceName, trackingCode: $trackingCode, shippedAt: $shippedAt, deliveredAt: $deliveredAt, estimatedDeliveryDate: $estimatedDeliveryDate, carrierTrackingUrl: $carrierTrackingUrl, expositor: $expositor, events: $events)';
}


}

/// @nodoc
abstract mixin class $RastreioCopyWith<$Res>  {
  factory $RastreioCopyWith(Rastreio value, $Res Function(Rastreio) _then) = _$RastreioCopyWithImpl;
@useResult
$Res call({
 String? status, String? carrier,@JsonKey(name: 'service_name') String? serviceName,@JsonKey(name: 'tracking_code') String? trackingCode,@JsonKey(name: 'shipped_at') DateTime? shippedAt,@JsonKey(name: 'delivered_at') DateTime? deliveredAt,@JsonKey(name: 'estimated_delivery_date') DateTime? estimatedDeliveryDate,@JsonKey(name: 'carrier_tracking_url') String? carrierTrackingUrl, Map<String, dynamic>? expositor, List<RastreioEvento> events
});




}
/// @nodoc
class _$RastreioCopyWithImpl<$Res>
    implements $RastreioCopyWith<$Res> {
  _$RastreioCopyWithImpl(this._self, this._then);

  final Rastreio _self;
  final $Res Function(Rastreio) _then;

/// Create a copy of Rastreio
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? status = freezed,Object? carrier = freezed,Object? serviceName = freezed,Object? trackingCode = freezed,Object? shippedAt = freezed,Object? deliveredAt = freezed,Object? estimatedDeliveryDate = freezed,Object? carrierTrackingUrl = freezed,Object? expositor = freezed,Object? events = null,}) {
  return _then(_self.copyWith(
status: freezed == status ? _self.status : status // ignore: cast_nullable_to_non_nullable
as String?,carrier: freezed == carrier ? _self.carrier : carrier // ignore: cast_nullable_to_non_nullable
as String?,serviceName: freezed == serviceName ? _self.serviceName : serviceName // ignore: cast_nullable_to_non_nullable
as String?,trackingCode: freezed == trackingCode ? _self.trackingCode : trackingCode // ignore: cast_nullable_to_non_nullable
as String?,shippedAt: freezed == shippedAt ? _self.shippedAt : shippedAt // ignore: cast_nullable_to_non_nullable
as DateTime?,deliveredAt: freezed == deliveredAt ? _self.deliveredAt : deliveredAt // ignore: cast_nullable_to_non_nullable
as DateTime?,estimatedDeliveryDate: freezed == estimatedDeliveryDate ? _self.estimatedDeliveryDate : estimatedDeliveryDate // ignore: cast_nullable_to_non_nullable
as DateTime?,carrierTrackingUrl: freezed == carrierTrackingUrl ? _self.carrierTrackingUrl : carrierTrackingUrl // ignore: cast_nullable_to_non_nullable
as String?,expositor: freezed == expositor ? _self.expositor : expositor // ignore: cast_nullable_to_non_nullable
as Map<String, dynamic>?,events: null == events ? _self.events : events // ignore: cast_nullable_to_non_nullable
as List<RastreioEvento>,
  ));
}

}


/// Adds pattern-matching-related methods to [Rastreio].
extension RastreioPatterns on Rastreio {
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

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _Rastreio value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _Rastreio() when $default != null:
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

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _Rastreio value)  $default,){
final _that = this;
switch (_that) {
case _Rastreio():
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

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _Rastreio value)?  $default,){
final _that = this;
switch (_that) {
case _Rastreio() when $default != null:
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

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( String? status,  String? carrier, @JsonKey(name: 'service_name')  String? serviceName, @JsonKey(name: 'tracking_code')  String? trackingCode, @JsonKey(name: 'shipped_at')  DateTime? shippedAt, @JsonKey(name: 'delivered_at')  DateTime? deliveredAt, @JsonKey(name: 'estimated_delivery_date')  DateTime? estimatedDeliveryDate, @JsonKey(name: 'carrier_tracking_url')  String? carrierTrackingUrl,  Map<String, dynamic>? expositor,  List<RastreioEvento> events)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _Rastreio() when $default != null:
return $default(_that.status,_that.carrier,_that.serviceName,_that.trackingCode,_that.shippedAt,_that.deliveredAt,_that.estimatedDeliveryDate,_that.carrierTrackingUrl,_that.expositor,_that.events);case _:
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

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( String? status,  String? carrier, @JsonKey(name: 'service_name')  String? serviceName, @JsonKey(name: 'tracking_code')  String? trackingCode, @JsonKey(name: 'shipped_at')  DateTime? shippedAt, @JsonKey(name: 'delivered_at')  DateTime? deliveredAt, @JsonKey(name: 'estimated_delivery_date')  DateTime? estimatedDeliveryDate, @JsonKey(name: 'carrier_tracking_url')  String? carrierTrackingUrl,  Map<String, dynamic>? expositor,  List<RastreioEvento> events)  $default,) {final _that = this;
switch (_that) {
case _Rastreio():
return $default(_that.status,_that.carrier,_that.serviceName,_that.trackingCode,_that.shippedAt,_that.deliveredAt,_that.estimatedDeliveryDate,_that.carrierTrackingUrl,_that.expositor,_that.events);case _:
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

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( String? status,  String? carrier, @JsonKey(name: 'service_name')  String? serviceName, @JsonKey(name: 'tracking_code')  String? trackingCode, @JsonKey(name: 'shipped_at')  DateTime? shippedAt, @JsonKey(name: 'delivered_at')  DateTime? deliveredAt, @JsonKey(name: 'estimated_delivery_date')  DateTime? estimatedDeliveryDate, @JsonKey(name: 'carrier_tracking_url')  String? carrierTrackingUrl,  Map<String, dynamic>? expositor,  List<RastreioEvento> events)?  $default,) {final _that = this;
switch (_that) {
case _Rastreio() when $default != null:
return $default(_that.status,_that.carrier,_that.serviceName,_that.trackingCode,_that.shippedAt,_that.deliveredAt,_that.estimatedDeliveryDate,_that.carrierTrackingUrl,_that.expositor,_that.events);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _Rastreio implements Rastreio {
  const _Rastreio({this.status, this.carrier, @JsonKey(name: 'service_name') this.serviceName, @JsonKey(name: 'tracking_code') this.trackingCode, @JsonKey(name: 'shipped_at') this.shippedAt, @JsonKey(name: 'delivered_at') this.deliveredAt, @JsonKey(name: 'estimated_delivery_date') this.estimatedDeliveryDate, @JsonKey(name: 'carrier_tracking_url') this.carrierTrackingUrl, final  Map<String, dynamic>? expositor, final  List<RastreioEvento> events = const []}): _expositor = expositor,_events = events;
  factory _Rastreio.fromJson(Map<String, dynamic> json) => _$RastreioFromJson(json);

@override final  String? status;
@override final  String? carrier;
@override@JsonKey(name: 'service_name') final  String? serviceName;
@override@JsonKey(name: 'tracking_code') final  String? trackingCode;
@override@JsonKey(name: 'shipped_at') final  DateTime? shippedAt;
@override@JsonKey(name: 'delivered_at') final  DateTime? deliveredAt;
@override@JsonKey(name: 'estimated_delivery_date') final  DateTime? estimatedDeliveryDate;
@override@JsonKey(name: 'carrier_tracking_url') final  String? carrierTrackingUrl;
 final  Map<String, dynamic>? _expositor;
@override Map<String, dynamic>? get expositor {
  final value = _expositor;
  if (value == null) return null;
  if (_expositor is EqualUnmodifiableMapView) return _expositor;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableMapView(value);
}

 final  List<RastreioEvento> _events;
@override@JsonKey() List<RastreioEvento> get events {
  if (_events is EqualUnmodifiableListView) return _events;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_events);
}


/// Create a copy of Rastreio
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$RastreioCopyWith<_Rastreio> get copyWith => __$RastreioCopyWithImpl<_Rastreio>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$RastreioToJson(this, );
}

@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is _Rastreio&&(identical(other.status, status) || other.status == status)&&(identical(other.carrier, carrier) || other.carrier == carrier)&&(identical(other.serviceName, serviceName) || other.serviceName == serviceName)&&(identical(other.trackingCode, trackingCode) || other.trackingCode == trackingCode)&&(identical(other.shippedAt, shippedAt) || other.shippedAt == shippedAt)&&(identical(other.deliveredAt, deliveredAt) || other.deliveredAt == deliveredAt)&&(identical(other.estimatedDeliveryDate, estimatedDeliveryDate) || other.estimatedDeliveryDate == estimatedDeliveryDate)&&(identical(other.carrierTrackingUrl, carrierTrackingUrl) || other.carrierTrackingUrl == carrierTrackingUrl)&&const DeepCollectionEquality().equals(other._expositor, _expositor)&&const DeepCollectionEquality().equals(other._events, _events));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,status,carrier,serviceName,trackingCode,shippedAt,deliveredAt,estimatedDeliveryDate,carrierTrackingUrl,const DeepCollectionEquality().hash(_expositor),const DeepCollectionEquality().hash(_events));

@override
String toString() {
  return 'Rastreio(status: $status, carrier: $carrier, serviceName: $serviceName, trackingCode: $trackingCode, shippedAt: $shippedAt, deliveredAt: $deliveredAt, estimatedDeliveryDate: $estimatedDeliveryDate, carrierTrackingUrl: $carrierTrackingUrl, expositor: $expositor, events: $events)';
}


}

/// @nodoc
abstract mixin class _$RastreioCopyWith<$Res> implements $RastreioCopyWith<$Res> {
  factory _$RastreioCopyWith(_Rastreio value, $Res Function(_Rastreio) _then) = __$RastreioCopyWithImpl;
@override @useResult
$Res call({
 String? status, String? carrier,@JsonKey(name: 'service_name') String? serviceName,@JsonKey(name: 'tracking_code') String? trackingCode,@JsonKey(name: 'shipped_at') DateTime? shippedAt,@JsonKey(name: 'delivered_at') DateTime? deliveredAt,@JsonKey(name: 'estimated_delivery_date') DateTime? estimatedDeliveryDate,@JsonKey(name: 'carrier_tracking_url') String? carrierTrackingUrl, Map<String, dynamic>? expositor, List<RastreioEvento> events
});




}
/// @nodoc
class __$RastreioCopyWithImpl<$Res>
    implements _$RastreioCopyWith<$Res> {
  __$RastreioCopyWithImpl(this._self, this._then);

  final _Rastreio _self;
  final $Res Function(_Rastreio) _then;

/// Create a copy of Rastreio
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? status = freezed,Object? carrier = freezed,Object? serviceName = freezed,Object? trackingCode = freezed,Object? shippedAt = freezed,Object? deliveredAt = freezed,Object? estimatedDeliveryDate = freezed,Object? carrierTrackingUrl = freezed,Object? expositor = freezed,Object? events = null,}) {
  return _then(_Rastreio(
status: freezed == status ? _self.status : status // ignore: cast_nullable_to_non_nullable
as String?,carrier: freezed == carrier ? _self.carrier : carrier // ignore: cast_nullable_to_non_nullable
as String?,serviceName: freezed == serviceName ? _self.serviceName : serviceName // ignore: cast_nullable_to_non_nullable
as String?,trackingCode: freezed == trackingCode ? _self.trackingCode : trackingCode // ignore: cast_nullable_to_non_nullable
as String?,shippedAt: freezed == shippedAt ? _self.shippedAt : shippedAt // ignore: cast_nullable_to_non_nullable
as DateTime?,deliveredAt: freezed == deliveredAt ? _self.deliveredAt : deliveredAt // ignore: cast_nullable_to_non_nullable
as DateTime?,estimatedDeliveryDate: freezed == estimatedDeliveryDate ? _self.estimatedDeliveryDate : estimatedDeliveryDate // ignore: cast_nullable_to_non_nullable
as DateTime?,carrierTrackingUrl: freezed == carrierTrackingUrl ? _self.carrierTrackingUrl : carrierTrackingUrl // ignore: cast_nullable_to_non_nullable
as String?,expositor: freezed == expositor ? _self._expositor : expositor // ignore: cast_nullable_to_non_nullable
as Map<String, dynamic>?,events: null == events ? _self._events : events // ignore: cast_nullable_to_non_nullable
as List<RastreioEvento>,
  ));
}


}

// dart format on
