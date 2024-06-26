<?php

namespace Azuriom\Plugin\Shop\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\Shop\Models\Gateway;
use Azuriom\Plugin\Shop\Payment\PaymentManager;
use Azuriom\Plugin\Shop\Requests\GatewayRequest;

class GatewayController extends Controller
{
    /**
     * The payment manager instance.
     */
    protected PaymentManager $paymentManager;

    /**
     * Create a new controller instance.
     */
    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $gateways = Gateway::all()
            ->filter(fn (Gateway $gateway) => $gateway->isSupported());

        $gatewayTypes = $gateways->pluck('type');

        $paymentMethods = $this->paymentManager->getPaymentMethods()
            ->keys()
            ->filter(fn ($type) => ! $gatewayTypes->contains($type));

        return view('shop::admin.gateways.index', [
            'gateways' => $gateways,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(string $type)
    {
        return view('shop::admin.gateways.create', [
            'type' => $this->paymentManager->getPaymentMethodOrFail($type),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(GatewayRequest $request)
    {
        $type = $request->input('type');

        $method = $this->paymentManager->getPaymentMethodOrFail($type);

        $data = $this->validate($request, $method->rules());

        Gateway::create([
            'data' => $data,
            'type' => $type,
        ] + $request->validated());

        return to_route('shop.admin.gateways.index')
            ->with('success', trans('messages.status.success'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Gateway $gateway)
    {
        return view('shop::admin.gateways.edit', [
            'type' => $gateway->paymentMethod(),
            'gateway' => $gateway,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(GatewayRequest $request, Gateway $gateway)
    {
        $data = $this->validate($request, $gateway->paymentMethod()->rules());

        $gateway->update(['data' => $data] + $request->validated());

        return to_route('shop.admin.gateways.index')
            ->with('success', trans('messages.status.success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \LogicException
     */
    public function destroy(Gateway $gateway)
    {
        $gateway->delete();

        return to_route('shop.admin.gateways.index')
            ->with('success', trans('messages.status.success'));
    }
}
