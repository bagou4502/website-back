<?php

namespace App\Http\Controllers\Api\Client\Web\Admin\Products;

use App\Http\Controllers\Controller;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ProductsController extends Controller
{
    /**
     * Liste des produits.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listProducts(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $page = $request->input('page', 1);
        $perPage = $request->input('perpage', 10);
        $search = $request->input('search', '');
        $productsQuery = Products::query();

        if ($search) {
            $productsQuery->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('description', 'LIKE', '%' . $search . '%');
            });
        }
        $total = ceil($productsQuery->count()/$perPage);
        $products = $productsQuery->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json(['status' => 'success', 'data' => $products, 'total' => $total]);
    }

    /**
     * Créer un produit.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createProduct(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'tag' => 'required',
            'version' => 'required',
            'price' => 'required|numeric',
            'sxcname' => 'required',
            'bbb_id' => 'required',
            'link' => 'required',
            'licensed' => 'required',
            'isnew' => 'required',
            'autoinstaller' => 'required',
            'recurrent' => 'required',
            'tab' => 'required',
            'tabroute' => 'nullable',
            'description' => 'required'
        ]);

        $user = auth('sanctum')->user();
        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $productName = $request->input('name');
        $productTag = $request->input('tag');
        $productVersion = $request->input('version');
        $productPrice = $request->input('price');
        $productSxcName = $request->input('sxcname');
        $productBBBId = $request->input('bbb_id');
        $productLink = json_decode($request->input('link'), true);
        $productLicensed = $request->input('licensed');
        $productNew = $request->input('isnew');
        $productAutoInstaller = $request->input('autoinstaller');
        $productRecurrent = $request->input('recurrent');
        $productTab = $request->input('tab');
        $productTabRoute = $request->input('tabroute') ? $request->input('tabroute') : '';
        $productDescription = $request->input('description');

        // Créer le produit sur Stripe
        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Bearer ' . config('services.stripe.secret'),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post('https://api.stripe.com/v1/products', [
            'name' => $productName,
        ]);

        if ($response->failed()) {
            return response()->json(['status' => 'error', 'message' => 'Failed to create product on Stripe'], 500);
        }

        $stripeProduct = $response->json();
        $stripeProductId = $stripeProduct['id'];
        if($productPrice == 0) {
            $productPrice +=1;
        }

        // Créer le prix du produit sur Stripe
        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Bearer ' . config('services.stripe.secret'),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post('https://api.stripe.com/v1/prices', [
            'product' => $stripeProductId,
            'unit_amount' => $productPrice * 100, // Le prix est en centimes
            'currency' => 'eur',
        ]);

        if ($response->failed()) {
            return response()->json(['status' => 'error', 'message' => 'Failed to create product price on Stripe'], 500);
        }

        $stripePrice = $response->json();
        $stripePriceId = $stripePrice['id'];

        // Stocker les données dans la table "products"
        $product = Products::create([
            'name' => $productName,
            'tag' => $productTag,
            'version' => $productVersion,
            'price' => $productPrice,
            'sxcname' => $productSxcName,
            'bbb_id' => $productBBBId,
            'link' => $productLink,
            'licensed' => $productLicensed,
            'new' => $productNew,
            'autoinstaller' => $productAutoInstaller,
            'recurrent' => $productRecurrent,
            'tab' => $productTab,
            'tabroute' => $productTabRoute,
            'description' => $productDescription,
            'stripe_id' => $stripeProductId,
            'stripe_price_id' => $stripePriceId,
        ]);

        return response()->json(['status' => 'success', 'message' => 'done', 'data' => $product], 202);
    }

    /**
     * Modifier un produit.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateProduct(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'tag' => 'required',
            'version' => 'required',
            'price' => 'required|numeric',
            'sxcname' => 'required',
            'bbb_id' => 'required',
            'link' => 'required',
            'licensed' => 'required',
            'isnew' => 'required',
            'autoinstaller' => 'required',
            'recurrent' => 'required',
            'tab' => 'required',
            'tabroute' => 'required',
            'description' => 'required'
        ]);
        $user = auth('sanctum')->user();
        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $product = Products::find($id);

        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
        }


        if($request->input('price') !== $product->price) {
            $response = Http::asForm()->withHeaders([
                'Authorization' => 'Bearer ' . config('services.stripe.secret'),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->post('https://api.stripe.com/v1/prices', [
                'product' => $product->stripe_id,
                'unit_amount' => $request->input('price') * 100, // Le prix est en centimes
                'currency' => 'eur',
            ]);
            if ($response->failed()) {
                return response()->json(['status' => 'error', 'message' => 'Failed to create product price on Stripe'], 500);
            }

            $stripePrice = $response->json();
            $stripePriceId = $stripePrice['id'];
            $product->stripe_price_id = $stripePriceId;
        }
        $product->name = $request->input('name');
        $product->tag = $request->input('tag');
        $product->version = $request->input('version');
        $product->price = $request->input('price');
        $product->sxcname = $request->input('sxcname');
        $product->bbb_id = $request->input('bbb_id');
        $product->link = json_decode($request->input('link'), true);
        $product->licensed = $request->input('licensed');
        $product->new = $request->input('isnew');
        $product->autoinstaller = $request->input('autoinstaller');
        $product->recurrent = $request->input('recurrent');
        $product->tab = $request->input('tab');
        $product->tabroute = $request->input('tabroute');
        $product->description = $request->input('description');

        // Mettez à jour d'autres champs si nécessaire
        $product->save();

        return response()->json(['status' => 'success', 'message' => 'Product updated', 'data' => $product]);
    }

    /**
     * Synchronise les produits avec Stripe.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncProducts()
    {
        $user = auth('sanctum')->user();
        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $products = Products::where(function ($query) {
            $query->where(function ($subquery) {
                $subquery->whereNotNull('stripe_id')
                    ->orWhereNotNull('stripe_price_id');
            })
                ->orWhere('price', '>', 0);
        })->get();
       // $products = Products::all();
        foreach ($products as $product) {
            if (empty($product->stripe_id) || empty($product->stripe_price_id)) {
                // Créer le produit sur Stripe
                $response = Http::asForm()->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.stripe.secret'),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])->post('https://api.stripe.com/v1/products', [
                    'name' => $product->name,
                ]);
                if ($response->failed()) {
                    return response()->json(['status' => 'error', 'message' => 'Failed to create product on Stripe'], 500);
                }

                $stripeProduct = $response->json();
                $stripeProductId = $stripeProduct['id'];

                // Créer le prix du produit sur Stripe
                $response = Http::asForm()->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.stripe.secret'),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])->post('https://api.stripe.com/v1/prices', [
                    'product' => $stripeProductId,
                    'unit_amount' => $product->price * 100, // Le prix est en centimes
                    'currency' => 'eur',
                ]);

                if ($response->failed()) {
                    return response()->json(['status' => 'error', 'message' => 'Failed to create product price on Stripe'], 500);
                }

                $stripePrice = $response->json();
                $stripePriceId = $stripePrice['id'];

                // Mettre à jour les identifiants Stripe dans la table "products"
                $product->stripe_id = $stripeProductId;
                $product->stripe_price_id = $stripePriceId;
                $product->save();
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Products synchronized with Stripe']);
    }

}
