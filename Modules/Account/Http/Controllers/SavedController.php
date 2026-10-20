<?php

namespace Modules\Account\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Account\Models\Member;
use Modules\Account\Models\SavedListing;
use Modules\Listing\Models\Listing;
use Modules\Listing\Models\ListingType;
use Modules\Site\Site;

/** Save for later: the member's saved properties. Idempotent PUT / DELETE so the heart can update optimistically. */
class SavedController
{
    public function index(Request $request): View
    {
        /** @var Member $member */
        $member = Auth::guard('member')->user();
        $listings = Listing::query()->with(['images', 'masterplan.plots:id,masterplan_id,status'])->published()
            ->whereIn('id', SavedListing::query()->where('member_id', $member->id)->select('listing_id'))
            ->orderByDesc(SavedListing::query()->select('created_at')->whereColumn('listing_id', 'listings.id')->where('member_id', $member->id)->limit(1))
            ->get();

        $types = ListingType::registry()->values();
        $counts = $listings->countBy('type');
        $tabs = $types->filter(fn (ListingType $t) => ($counts[$t->slug] ?? 0) > 0)->map(fn (ListingType $t) => ['key' => $t->slug, 'label' => $t->name, 'count' => $counts[$t->slug]])->values()->all();
        $settings = Site::settings();
        $lines = $listings->take(15)->map(fn (Listing $l) => '- '.$l->title.' ('.$l->location.', '.$l->reference().') '.Site::baseUrl().$l->url())->implode("\n");

        return view('account::saved', Site::page(['title' => __('account::saved.title'), 'nav' => ''], [
            'listings' => $listings,
            'tabs' => $tabs,
            'total' => $listings->count(),
            'askAll' => $listings->isEmpty() ? '' : Site::whatsapp($settings, __('account::saved.whatsapp_message', ['list' => $lines])),
        ]));
    }

    public function save(Request $request, Listing $listing): JsonResponse
    {
        abort_unless($listing->published, 404);
        SavedListing::query()->firstOrCreate(['member_id' => $request->user('member')->id, 'listing_id' => $listing->id]);

        return $this->state($request, $listing);
    }

    public function remove(Request $request, Listing $listing): JsonResponse
    {
        SavedListing::query()->where('member_id', $request->user('member')->id)->where('listing_id', $listing->id)->delete();

        return $this->state($request, $listing);
    }

    private function state(Request $request, Listing $listing): JsonResponse
    {
        return response()->json([
            'saved' => SavedListing::query()->where('member_id', $request->user('member')->id)->where('listing_id', $listing->id)->exists(),
            'count' => SavedListing::query()->where('member_id', $request->user('member')->id)->count(),
        ]);
    }

    /** A guest tapped Save: remember it so signing in with Google (a full-page trip) can finish the save. */
    public function pending(Request $request): JsonResponse
    {
        $request->session()->put('pending_save', (int) $request->input('listing'));

        return response()->json(['ok' => true]);
    }
}
