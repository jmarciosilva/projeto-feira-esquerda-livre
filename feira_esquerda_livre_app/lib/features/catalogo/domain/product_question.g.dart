// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'product_question.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_ProductQuestion _$ProductQuestionFromJson(Map<String, dynamic> json) =>
    _ProductQuestion(
      id: (json['id'] as num).toInt(),
      question: json['question'] as String,
      answer: json['answer'] as String?,
      askerFirstName: json['asker_first_name'] as String,
      answeredAt: json['answered_at'] == null
          ? null
          : DateTime.parse(json['answered_at'] as String),
      createdAt: json['created_at'] == null
          ? null
          : DateTime.parse(json['created_at'] as String),
    );

Map<String, dynamic> _$ProductQuestionToJson(_ProductQuestion instance) =>
    <String, dynamic>{
      'id': instance.id,
      'question': instance.question,
      'answer': instance.answer,
      'asker_first_name': instance.askerFirstName,
      'answered_at': instance.answeredAt?.toIso8601String(),
      'created_at': instance.createdAt?.toIso8601String(),
    };
