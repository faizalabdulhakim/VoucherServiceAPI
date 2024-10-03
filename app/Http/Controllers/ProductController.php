<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Resources\ListResponseResource;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $size = request()->query('size', 10);
        $q = request()->query('q', null);
        $sort = request()->query('sort', null);
        $column = request()->query('column', null);

        $products = Product::when($q, function ($query, $q) {
            return $query->where('name', 'like', '%' . $q . '%');
        })
            ->when($sort, function ($query, $sort) use ($column) {
                return $query->orderBy($column, $sort);
            })
            ->paginate($size);

        return response()->json(
            new ListResponseResource(
                $products,
                Response::HTTP_OK,
                "Products retrieved successfully"
            ),
            Response::HTTP_OK
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'string|required|max:255',
            'description' => 'nullable',
            'price' => 'required|numeric',
            'stock' => 'required|numeric',
        ]);

        try {
            $product = Product::create($request->all());
            return response()->json([
                'status' => Response::HTTP_CREATED,
                'message' => 'Product created successfully',
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Product created failed',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::where('id', $id)->first();

        if (!$product) {
            return response()->json(
                [
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Product not found',
                    'data' => null
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json(
            [
                'status' => Response::HTTP_OK,
                'message' => 'Product retrieved successfully',
                'data' => $product,
            ],
            Response::HTTP_OK
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'string|nullable|max:255',
            'description' => 'nullable',
            'price' => 'nullable|numeric',
            'stock' => 'nullable|numeric',
        ]);

        $product = Product::where('id', $id)->first();

        if (!$product) {
            return response()->json(
                [
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Product not found',
                    'data' => null
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        try {
            $product->update($request->all());
            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Product updated successfully',
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Product updated failed',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::where('id', $id)->first();

        if (!$product) {
            return response()->json(
                [
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Product not found',
                    'data' => null
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        try {
            $product->delete();
            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Product deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Product deleted failed',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
