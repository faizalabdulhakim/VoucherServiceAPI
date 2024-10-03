<?php

namespace App\Http\Controllers;

use App\Http\Resources\ListResponseResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
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

        $orders = Order::when($q, function ($query, $q) {
            return $query->where('code', 'like', '%' . $q . '%');
        })
            ->when($sort, function ($query, $sort) use ($column) {
                return $query->orderBy($column, $sort);
            })
            ->paginate($size);

        return response()->json(
            new ListResponseResource(
                $orders,
                Response::HTTP_OK,
                "Orders retrieved successfully"
            ),
            Response::HTTP_OK
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'voucher_code' => 'nullable|exists:vouchers,code',
        ]);

        $totalPrice = 0;
        $discount = 0;
        $finalPrice = 0;

        DB::beginTransaction();

        try {
            $order = Order::create([
                'user_id' => $validated['user_id'],
                'voucher_code' => $validated['voucher_code'] ?? null,
                'total_price' => 0,
                'discount' => 0,
                'final_price' => 0,
            ]);

            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['id']);
                $quantity = $productData['quantity'];

                if ($product->stock < $quantity) {
                    throw new \Exception("Not enough stock for product: {$product->name}");
                }

                $price = $product->price;
                $totalPrice += $price * $quantity;
                $product->decrement('stock', $quantity);

                $order->products()->attach($product->id, [
                    'quantity' => $quantity,
                    'price' => $price,
                ]);
            }

            if ($order->voucher_code) {

                $voucher = $order->voucher;

                if ($voucher->activation_date) {
                    if ($voucher->activation_date && now()->lessThan($voucher->activation_date)) {
                        throw new \Exception("Voucher {$voucher->code} is not active yet.");
                    }
                }


                if (now()->greaterThanOrEqualTo($voucher->activation_date) && now()->lessThanOrEqualTo($voucher->expiry_date)) {
                    $voucher->update(['is_active' => true]);
                }

                if ($voucher->is_active && now()->lessThanOrEqualTo($voucher->expiry_date)) {
                    $discount = $totalPrice * ($voucher->discount / 100);
                }
            }

            $finalPrice = $totalPrice - $discount;

            $order->update([
                'total_price' => $totalPrice,
                'discount' => $discount,
                'final_price' => $finalPrice,
            ]);

            DB::commit();

            return response()->json([
                'status' => Response::HTTP_CREATED,
                'message' => 'Order created successfully.',
                'order' => $order->load('products'),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Failed to create order.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $order = Order::where('id', $id)->first();

        if (!$order) {
            return response()->json(
                [
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Order not found',
                    'data' => null
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json(
            [
                'status' => Response::HTTP_OK,
                'message' => 'Order retrieved successfully',
                'data' => $order,
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
            'code' => 'string|nullable|max:50',
            'discount' => 'nullable|numeric',
            'expiry_date' => 'nullable|date',
            'activation_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $order = Order::where('id', $id)->first();

        if (!$order) {
            return response()->json(
                [
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Order not found',
                    'data' => null
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        try {
            $order->update($request->all());
            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Order updated successfully',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Order updated failed',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = Order::where('id', $id)->first();

        if (!$order) {
            return response()->json(
                [
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Order not found',
                    'data' => null
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        try {
            $order->delete();
            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Order deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Order deleted failed',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
