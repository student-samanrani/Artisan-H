<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SubCategory;
use App\Models\TempImage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProductController extends Controller
{

    public function index(Request $request){
        $products = Product::latest('id')->with('product_images');

        if($request->get('keyword') != ""){
            $products = $products->where('title','like','%'.$request->get('keyword').'%');
        }
        $products = $products->paginate();
        $data['products'] = $products;
        return view('admin.products.list',$data);
    }
    public function create(){
        $data = [];
        $categories = Category::orderBy('name','ASC')->get();
        $brands = Brand::orderBy('name','ASC')->get();
        $data['categories'] = $categories; 
        $data['brands'] = $brands; 
        return view('admin.products.create',$data);
    }

    public function store(Request $request){

        // dd($request->image_array);
        // exit();
        $rules = [
            'title' => 'required' ,
            'slug' => 'required|unique:products',
            'price' => 'required|numeric',
            'sku' => 'required|unique:products',
            'track_qty' => 'required|in:Yes,No',
            'category' => 'required|numeric',
            'is_featured' => 'required|in:Yes,No',
        ];

        if (!empty($request->track_qty) && $request->track_qty == 'Yes'){
            $rules['qty'] = 'required|numeric';
        }

        $validator = Validator::make($request->all(),$rules);

        if ($validator->passes()){
            $product = new Product();
            $product->title = $request->title;
            $product->slug = $request->slug;
            $product->description = $request->description;
            $product->price = $request->price;
            $product->compare_price = $request->compare_price;
            $product->sku = $request->sku;
            $product->barcode = $request->barcode;
            $product->track_qty = $request->track_qty;
            $product->qty = $request->qty;
            $product->status = $request->status;
            $product->category_id = $request->category;
            $product->sub_category_id = $request->sub_category;
            $product->brand_id = $request->brand;
            $product->is_featured = $request->is_featured;
            $product->save();


            // save product pics
            if(!empty($request->image_array)){
                $manager = new ImageManager(new Driver());
                foreach($request->image_array as $temp_image_id){

                    $tempImageInfo = TempImage::find($temp_image_id);
                    $extArray = explode('.',$tempImageInfo->name);
                    $ext = last($extArray);

                    $productImage = new ProductImage();
                    $productImage->product_id = $product->id;
                    $productImage->image = 'NULL';
                    $productImage->save();
                    
                    $imageName = $product->id.'-'. $productImage->id.'-'.time().'.'.$ext;
                    $productImage->image  = $imageName;
                    $productImage->save();

                    //generate product thumbnail

                    //large image
                    $sourcePath = public_path().'/temp/'.$tempImageInfo->name;
                    $destPath = public_path().'/uploads/product/large/'.$imageName;
                    $img = $manager->read($sourcePath);
                    $img = $img->resize(1400, 600);
                    $img->save($destPath); 

                    //small image
                    $sourcePath = public_path().'/temp/'.$tempImageInfo->name;
                    $destPath = public_path().'/uploads/product/small/'.$imageName;
                    $img = $manager->read($sourcePath);
                    $img = $img->resize(300, 300);
                    $img->save($destPath); 

                }
            }
            $request->session()->flash('success','Product added successfully');


            return response()->json([
                'status' => true,
                'message' => 'Product added successfully'
            ]);

        } else {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ]);
        }
    }

    public function edit($id, Request $request){
        $product = Product::find($id);
        $subCategories = SubCategory::where('category_id',$product->category_id)->get();

        $data = [];
        $data['product'] = $product;
        $data['subCategories'] = $subCategories;
        $categories = Category::orderBy('name','ASC')->get();
        $brands = Brand::orderBy('name','ASC')->get();
        $data['categories'] = $categories; 
        $data['brands'] = $brands; 
        return view('admin.products.edit',$data);
        
     }

     public function update($id, Request $request){
        $product = Product::find($id);
        $rules = [
            'title' => 'required' ,
            'slug' => 'required|unique:products,slug,'.$product->id.',id',
            'price' => 'required|numeric',
            'sku' => 'required|unique:products,sku,'.$product->id.',id',
            'track_qty' => 'required|in:Yes,No',
            'category' => 'required|numeric',
            'is_featured' => 'required|in:Yes,No',
        ];

        if (!empty($request->track_qty) && $request->track_qty == 'Yes'){
            $rules['qty'] = 'required|numeric';
        }

        $validator = Validator::make($request->all(),$rules);

        if ($validator->passes()){
            $product->title = $request->title;
            $product->slug = $request->slug;
            $product->description = $request->description;
            $product->price = $request->price;
            $product->compare_price = $request->compare_price;
            $product->sku = $request->sku;
            $product->barcode = $request->barcode;
            $product->track_qty = $request->track_qty;
            $product->qty = $request->qty;
            $product->status = $request->status;
            $product->category_id = $request->category;
            $product->sub_category_id = $request->sub_category;
            $product->brand_id = $request->brand;
            $product->is_featured = $request->is_featured;
            $product->save();


            $request->session()->flash('success','Product updated successfully');


            return response()->json([
                'status' => true,
                'message' => 'Product updated successfully'
            ]);

        } else {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ]);
        }
     }
}
