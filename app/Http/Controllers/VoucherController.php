<?php

namespace App\Http\Controllers;

use App\Http\Resources\ListResponseResource;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VoucherController extends Controller
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

        $vouchers = Voucher::when($q, function ($query, $q) {
            return $query->where('code', 'like', '%' . $q . '%');
        })
            ->when($sort, function ($query, $sort) use ($column) {
                return $query->orderBy($column, $sort);
            })
            ->paginate($size);

        return response()->json(
            new ListResponseResource(
                $vouchers,
                Response::HTTP_OK,
                "Vouchers retrieved successfully"
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
            'code' => 'string|required|max:50|unique:vouchers',
            'discount' => 'required|numeric|min:0|max:100',
            'expiry_date' => 'required|date_format:Y-m-d H:i:s',
            'activation_date' => 'nullable|date_format:Y-m-d H:i:s',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            if ($request->activation_date && $request->expiry_date) {
                if ($request->activation_date >= $request->expiry_date) {
                    return response()->json([
                        'status' => Response::HTTP_BAD_REQUEST,
                        'message' => 'Activation date cannot greater than expiry date',
                    ]);
                }
            }

            if (!$request->activation_date) {
                $request->merge(['is_active' => true]);
            }

            $voucher = Voucher::create($request->all());
            return response()->json([
                'status' => Response::HTTP_CREATED,
                'message' => 'Voucher created successfully',
                'data' => $voucher,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Voucher created failed',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $voucher = Voucher::where('id', $id)->first();

        if (!$voucher) {
            return response()->json(
                [
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Voucher not found',
                    'data' => null
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        return response()->json(
            [
                'status' => Response::HTTP_OK,
                'message' => 'Voucher retrieved successfully',
                'data' => $voucher,
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

        $voucher = Voucher::where('id', $id)->first();

        if (!$voucher) {
            return response()->json(
                [
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Voucher not found',
                    'data' => null
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        try {
            $voucher->update($request->all());
            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Voucher updated successfully',
                'data' => $voucher,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Voucher updated failed',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $voucher = Voucher::where('id', $id)->first();

        if (!$voucher) {
            return response()->json(
                [
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Voucher not found',
                    'data' => null
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        try {
            $voucher->delete();
            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Voucher deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Voucher deleted failed',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
