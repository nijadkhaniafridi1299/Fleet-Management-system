<?php

namespace App\Validator;

trait Product{
    protected $rules = [
        "product_name.ar" => "required_without:product_name.en",
        "categories.*" => "required|exists:product_categories,product_category_id",
        "price" => "required|numeric",
        "volume.*" => "nullable|numeric",
        "volume.unit" => "nullable|alpha_dash",
        "weight" => "nullable|numeric",
        "product_sort" => "nullable|numeric",
        "company_id" => "nullable|exists:companies,company_id",
        "product_images.*" => "image|mimes:jpg,jpeg,png,bmp|max:20000"
    ];
   
  protected $messages=[
        "product_name.ar.required_without" => "Please specify either english name or arabic.",
        "categories.*.required" => "Category is required.",
        "categories.*.exists" => "Please specify valid category.",
        "price.required" => "Please specify product price.",
        "price.numeric" => "Price should be a number.",
        "volume.length.numeric" => "Length should be a number.",
        "volume.width.numeric" => "Width should be a number.",
        "volume.height.numeric" => "Height should be a number.",
        "product_images.*.mimes" => "Only jpeg,png and bmp images are allowed.",
        "product_images.*.max" => "Maximum allowed size for an image is 20MB."
  ];

  protected $imageRules = [
    "product_images" => "mimes:jpeg,jpg,png,gif|max:100000"
  ];
}
