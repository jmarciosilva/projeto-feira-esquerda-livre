// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'product_question.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// dart format off
T _$identity<T>(T value) => value;

/// @nodoc
mixin _$ProductQuestion {

 int get id; String get question; String? get answer;@JsonKey(name: 'asker_first_name') String get askerFirstName;@JsonKey(name: 'answered_at') DateTime? get answeredAt;@JsonKey(name: 'created_at') DateTime? get createdAt;
/// Create a copy of ProductQuestion
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$ProductQuestionCopyWith<ProductQuestion> get copyWith => _$ProductQuestionCopyWithImpl<ProductQuestion>(this as ProductQuestion, _$identity);

  /// Serializes this ProductQuestion to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is ProductQuestion&&(identical(other.id, id) || other.id == id)&&(identical(other.question, question) || other.question == question)&&(identical(other.answer, answer) || other.answer == answer)&&(identical(other.askerFirstName, askerFirstName) || other.askerFirstName == askerFirstName)&&(identical(other.answeredAt, answeredAt) || other.answeredAt == answeredAt)&&(identical(other.createdAt, createdAt) || other.createdAt == createdAt));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,question,answer,askerFirstName,answeredAt,createdAt);

@override
String toString() {
  return 'ProductQuestion(id: $id, question: $question, answer: $answer, askerFirstName: $askerFirstName, answeredAt: $answeredAt, createdAt: $createdAt)';
}


}

/// @nodoc
abstract mixin class $ProductQuestionCopyWith<$Res>  {
  factory $ProductQuestionCopyWith(ProductQuestion value, $Res Function(ProductQuestion) _then) = _$ProductQuestionCopyWithImpl;
@useResult
$Res call({
 int id, String question, String? answer,@JsonKey(name: 'asker_first_name') String askerFirstName,@JsonKey(name: 'answered_at') DateTime? answeredAt,@JsonKey(name: 'created_at') DateTime? createdAt
});




}
/// @nodoc
class _$ProductQuestionCopyWithImpl<$Res>
    implements $ProductQuestionCopyWith<$Res> {
  _$ProductQuestionCopyWithImpl(this._self, this._then);

  final ProductQuestion _self;
  final $Res Function(ProductQuestion) _then;

/// Create a copy of ProductQuestion
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? id = null,Object? question = null,Object? answer = freezed,Object? askerFirstName = null,Object? answeredAt = freezed,Object? createdAt = freezed,}) {
  return _then(_self.copyWith(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as int,question: null == question ? _self.question : question // ignore: cast_nullable_to_non_nullable
as String,answer: freezed == answer ? _self.answer : answer // ignore: cast_nullable_to_non_nullable
as String?,askerFirstName: null == askerFirstName ? _self.askerFirstName : askerFirstName // ignore: cast_nullable_to_non_nullable
as String,answeredAt: freezed == answeredAt ? _self.answeredAt : answeredAt // ignore: cast_nullable_to_non_nullable
as DateTime?,createdAt: freezed == createdAt ? _self.createdAt : createdAt // ignore: cast_nullable_to_non_nullable
as DateTime?,
  ));
}

}


/// Adds pattern-matching-related methods to [ProductQuestion].
extension ProductQuestionPatterns on ProductQuestion {
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

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _ProductQuestion value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _ProductQuestion() when $default != null:
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

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _ProductQuestion value)  $default,){
final _that = this;
switch (_that) {
case _ProductQuestion():
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

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _ProductQuestion value)?  $default,){
final _that = this;
switch (_that) {
case _ProductQuestion() when $default != null:
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

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( int id,  String question,  String? answer, @JsonKey(name: 'asker_first_name')  String askerFirstName, @JsonKey(name: 'answered_at')  DateTime? answeredAt, @JsonKey(name: 'created_at')  DateTime? createdAt)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _ProductQuestion() when $default != null:
return $default(_that.id,_that.question,_that.answer,_that.askerFirstName,_that.answeredAt,_that.createdAt);case _:
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

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( int id,  String question,  String? answer, @JsonKey(name: 'asker_first_name')  String askerFirstName, @JsonKey(name: 'answered_at')  DateTime? answeredAt, @JsonKey(name: 'created_at')  DateTime? createdAt)  $default,) {final _that = this;
switch (_that) {
case _ProductQuestion():
return $default(_that.id,_that.question,_that.answer,_that.askerFirstName,_that.answeredAt,_that.createdAt);case _:
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

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( int id,  String question,  String? answer, @JsonKey(name: 'asker_first_name')  String askerFirstName, @JsonKey(name: 'answered_at')  DateTime? answeredAt, @JsonKey(name: 'created_at')  DateTime? createdAt)?  $default,) {final _that = this;
switch (_that) {
case _ProductQuestion() when $default != null:
return $default(_that.id,_that.question,_that.answer,_that.askerFirstName,_that.answeredAt,_that.createdAt);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _ProductQuestion implements ProductQuestion {
  const _ProductQuestion({required this.id, required this.question, this.answer, @JsonKey(name: 'asker_first_name') required this.askerFirstName, @JsonKey(name: 'answered_at') this.answeredAt, @JsonKey(name: 'created_at') this.createdAt});
  factory _ProductQuestion.fromJson(Map<String, dynamic> json) => _$ProductQuestionFromJson(json);

@override final  int id;
@override final  String question;
@override final  String? answer;
@override@JsonKey(name: 'asker_first_name') final  String askerFirstName;
@override@JsonKey(name: 'answered_at') final  DateTime? answeredAt;
@override@JsonKey(name: 'created_at') final  DateTime? createdAt;

/// Create a copy of ProductQuestion
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$ProductQuestionCopyWith<_ProductQuestion> get copyWith => __$ProductQuestionCopyWithImpl<_ProductQuestion>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$ProductQuestionToJson(this, );
}

@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is _ProductQuestion&&(identical(other.id, id) || other.id == id)&&(identical(other.question, question) || other.question == question)&&(identical(other.answer, answer) || other.answer == answer)&&(identical(other.askerFirstName, askerFirstName) || other.askerFirstName == askerFirstName)&&(identical(other.answeredAt, answeredAt) || other.answeredAt == answeredAt)&&(identical(other.createdAt, createdAt) || other.createdAt == createdAt));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,question,answer,askerFirstName,answeredAt,createdAt);

@override
String toString() {
  return 'ProductQuestion(id: $id, question: $question, answer: $answer, askerFirstName: $askerFirstName, answeredAt: $answeredAt, createdAt: $createdAt)';
}


}

/// @nodoc
abstract mixin class _$ProductQuestionCopyWith<$Res> implements $ProductQuestionCopyWith<$Res> {
  factory _$ProductQuestionCopyWith(_ProductQuestion value, $Res Function(_ProductQuestion) _then) = __$ProductQuestionCopyWithImpl;
@override @useResult
$Res call({
 int id, String question, String? answer,@JsonKey(name: 'asker_first_name') String askerFirstName,@JsonKey(name: 'answered_at') DateTime? answeredAt,@JsonKey(name: 'created_at') DateTime? createdAt
});




}
/// @nodoc
class __$ProductQuestionCopyWithImpl<$Res>
    implements _$ProductQuestionCopyWith<$Res> {
  __$ProductQuestionCopyWithImpl(this._self, this._then);

  final _ProductQuestion _self;
  final $Res Function(_ProductQuestion) _then;

/// Create a copy of ProductQuestion
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? id = null,Object? question = null,Object? answer = freezed,Object? askerFirstName = null,Object? answeredAt = freezed,Object? createdAt = freezed,}) {
  return _then(_ProductQuestion(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as int,question: null == question ? _self.question : question // ignore: cast_nullable_to_non_nullable
as String,answer: freezed == answer ? _self.answer : answer // ignore: cast_nullable_to_non_nullable
as String?,askerFirstName: null == askerFirstName ? _self.askerFirstName : askerFirstName // ignore: cast_nullable_to_non_nullable
as String,answeredAt: freezed == answeredAt ? _self.answeredAt : answeredAt // ignore: cast_nullable_to_non_nullable
as DateTime?,createdAt: freezed == createdAt ? _self.createdAt : createdAt // ignore: cast_nullable_to_non_nullable
as DateTime?,
  ));
}


}

// dart format on
