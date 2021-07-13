<?php

namespace App\Lib;

class Response {
  public static function success($message = null, $data = null, $code = null) {
    return response()->json([
      'error' => false,
      'message' => $message,
      'data' => $data,
    ], 200);
  }

  public static function error($message = null, $data = null, $code = null) {
    return response()->json([
      'error' => true,
      'code' => null,
      'message' => $message,
      'data' => $data,
    ], 400);
  }

  public static function forbidden($message = 'Forbidden access!', $data = null, $code = null) {
    return response()->json([
      'error' => true,
      'code' => null,
      'message' => $message,
      'data' => $data,
    ], 403);
  }

  public static function unauthorized($message = 'Unauthorized user!', $data = null, $code = null) {
    return response()->json([
      'error' => true,
      'code' => null,
      'message' => $message,
      'data' => $data,
    ], 401);
  }
}