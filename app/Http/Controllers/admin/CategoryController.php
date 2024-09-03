<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\TempImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use App\Models\Category;
class CategoryController extends Controller
{
     public function index(Request $request){
        $categories = Category::latest();
        if(!empty($request->get('keyword'))){
            $categories = $categories->where('name','like','%'.$request->get('keyword').'%');
        }
        $categories = $categories->paginate(10);
        return view('admin.category.list' , compact('categories'));

     }
     public function create(){
       return view('admin.category.create');
        
     }
     public function store(Request $request){
        // dd($request->all());
        // Validate the incoming request
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'slug' => 'required|unique:categories',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
    
        if ($validator->passes()){
    
            // Create a new category instance
            $category = new Category();
            $category->name = $request->name;
            $category->slug = $request->slug;
            $category->status = $request->status;
            $category->save();

            // Handle the image upload
            // if($request->hasFile('image')){
            //     $image = $request->file('image');
            //     $imageName = $category->id . '.' . $image->getClientOriginalExtension();
            //     $destinationPath = public_path('/uploads/category/thumb');
            //     $image->move($destinationPath, $imageName);

            //     // Save the image name in the database
            //     $category->image = $imageName;
            //     $category->save();
            // }
            

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = $category->id . '.' . $image->getClientOriginalExtension();
                $destinationPath = public_path('/uploads/category/thumb');
    
                // Resize and fit the image using Intervention
                $resizedImage = Image::make($image->getRealPath());
                $resizedImage->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                })->fit(300, 300)->save($destinationPath . '/' . $imageName);
    
                // Save the image name in the database
                $category->image = $imageName;
                $category->save();
            }
            // Flash a success message
            $request->session()->flash('success','Category added successfully');
    
            return response()->json([
                'status' => true,
                'message' => 'Category added successfully'
            ]);
    
        } else{
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }
    
    
     public function edit($categoryId, Request $request){
        $category = Category::find($categoryId);
        if (empty($category)){
            return redirect()->route('categories.index');
        }
        return view('admin.category.edit' , compact('category'));
        
     }
     public function update($categoryId, Request $request){
        $category = Category::find($categoryId);

        if (empty($category)){
            $request->session()->flash('error','Category not found');
            return response()->json([
                'status' => false,
                'notFound' => true,
                'message' => 'category not found'
            ]);
        }

        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'slug' => 'required|unique:categories,slug,'.$category->id.',id',
        ]);
        if ($validator->passes()){

            $category->name = $request->name;
            $category->slug = $request->slug;
            $category->status = $request->status;
            $category->save();

            $oldImage = $category->image;

            // save image here 
            if(!empty($request->image_id)){
                $tempImage = TempImage::find($request->image_id);
                $extArray = explode('.',$tempImage->name);
                $ext = last($extArray);

                $newImageName = $category->id.'-'.time().'.'.$ext; 
                // sPath = source path , dPath = destination path 
                $sPath = public_path().'/temp/'.$tempImage->name; 
                $dPath = public_path().'/uploads/category/thumb/'.$newImageName; 
                File::copy($sPath,$dPath);

                // generate image thumbnail 
                // $dPath = public_path().'/uploads/category/thumb/'.$newImageName; 
                // $img = Image::make($sPath);
                // $img->resize(450, 600);
                // $img->fit(450, 600 ,function($constraint){
                //     $constraint->upsize();
                // });
                // $img->save($dPath);
                
                $category->image = $newImageName;
                $category->save();

                // Delete old images here 
                File::delete(public_path().'/uploads/category/thumb/'.$oldImage);
                // File::delete(public_path().'/uploads/category/'.$oldImage);

                
            }

            $request->session()->flash('success','Category updated successfully');

            return response()->json([
                'status' => true,
                'message' => 'Category updated successfully'
            ]);

        } else{
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
        
     }
     public function destroy($categoryId, Request $request){
        $category = Category::find($categoryId);

        if (empty($category)){
        $request->session()->flash('error','Category not found');
            return response()->json([
                'status' => true,
                'message' => 'Category not found'
            ]);
            // return redirect()->route('categories.index');
        }
        File::delete(public_path().'/uploads/category/thumb/'.$category->image);
        // File::delete(public_path().'/uploads/category/'.$category->image);
        $category->delete();

        $request->session()->flash('success','Category deleted successfully');

        return response()->json([
            'status' => true,
            'message' => 'Category deleted successfully '
        ]);
     }
}
