<?php

namespace App\Http\Controllers\Api\Business;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\Animal;
use App\Models\User;
use App\Models\OwnershipTransfer;
use App\Models\OwnershipHistory;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\AuctionResource;
use App\Http\Resources\BidResource;
use App\Http\Controllers\Traits\OwnableAuthorization;
use App\Http\Controllers\Traits\SendsEmailNotifications;
use App\Services\AuctionPaymentService;
use App\Http\Controllers\Controller;

class AuctionController extends Controller
{
    use OwnableAuthorization, SendsEmailNotifications;
    
    private function canAccessAuction(Request $request, Auction $auction): bool
    {
        $userId = $this->getUserId($request);
        $userRole = $this->getUserRole($request);
        
        if ($userRole === 'Admin') {
            return true;
        }
        
        if ($auction->owner_id == $userId) {
            return true;
        }
        
        if ($userRole === 'Owner' && in_array($auction->status, ['active', 'ended'])) {
            return true;
        }
        
        if ($userRole === 'Manager') {
            $user = $this->getUser($request);
            if ($user && $user->managed_by) {
                return $auction->owner_id == $user->managed_by;
            }
            return true;
        }
        
        return false;
    }
    
    private function canModifyAuction(Request $request, Auction $auction): bool
    {
        $userId = $this->getUserId($request);
        $userRole = $this->getUserRole($request);
        
        if ($userRole === 'Admin') {
            return true;
        }
        
        if ($auction->owner_id == $userId) {
            return true;
        }
        
        if ($userRole === 'Manager') {
            $user = $this->getUser($request);
            if ($user && $user->managed_by) {
                return $auction->owner_id == $user->managed_by;
            }
            return true;
        }
        
        return false;
    }
    
    private function filterByRole(Request $request, $query)
    {
        $userId = $this->getUserId($request);
        $userRole = $this->getUserRole($request);
        
        if ($userRole === 'Admin') {
            return $query;
        }
        
        if ($userRole === 'Owner') {
            return $query->where(function ($q) use ($userId) {
                $q->where('owner_id', $userId)
                  ->orWhereIn('status', ['active', 'ended']);
            });
        }
        
        if ($userRole === 'Manager') {
            $user = $this->getUser($request);
            if ($user && $user->managed_by) {
                return $query->where('owner_id', $user->managed_by);
            }
            return $query;
        }
        
        return $query->where('owner_id', $userId);
    }
    
    private function canCreateAuctionForAnimal(Request $request, Animal $animal): bool
    {
        $userId = $this->getUserId($request);
        $userRole = $this->getUserRole($request);
        
        if ($userRole === 'Admin') {
            return true;
        }
        
        if ($userRole === 'Owner' && $animal->owner_id == $userId) {
            return true;
        }
        
        if ($userRole === 'Manager') {
            $user = $this->getUser($request);
            if ($user && $user->managed_by) {
                return $animal->owner_id == $user->managed_by;
            }
            return true;
        }
        
        return false;
    }

    public function index(Request $request)
    {
        $query = Auction::with(['animal', 'owner', 'winner', 'bids']);
        
        $userId = $this->getUserId($request);
        $userRole = $this->getUserRole($request);

        if ($request->has('view') && $request->view === 'all') {
            $myAuctions = [];
            $enrolledAuctions = [];
            
            $query = Auction::with(['animal', 'owner', 'winner', 'bids']);
            
            if ($userRole === 'Admin') {
                $query = $query;
            } elseif ($userRole === 'Owner') {
                $myQuery = clone $query;
                $myQuery->where('owner_id', $userId);
                
                $bidAuctionIds = Bid::where('user_id', $userId)->pluck('auction_id')->toArray();
                $enrolledQuery = clone $query;
                $enrolledQuery->whereIn('id', $bidAuctionIds)->where('owner_id', '!=', $userId);
                
                $myAuctions = $myQuery->where('status', 'active')->orderBy('ends_at', 'asc')->get();
                $enrolledAuctions = $enrolledQuery->where('status', 'active')->orderBy('ends_at', 'asc')->get();
            } elseif ($userRole === 'Manager') {
                $user = $this->getUser($request);
                if ($user && $user->managed_by) {
                    $myQuery = clone $query;
                    $myQuery->where('owner_id', $user->managed_by);
                    $myAuctions = $myQuery->where('status', 'active')->orderBy('ends_at', 'asc')->get();
                }
                
                $bidAuctionIds = Bid::where('user_id', $userId)->pluck('auction_id')->toArray();
                $enrolledQuery = clone $query;
                $enrolledAuctions = $enrolledQuery->whereIn('id', $bidAuctionIds)->where('status', 'active')->orderBy('ends_at', 'asc')->get();
            } else {
                $bidAuctionIds = Bid::where('user_id', $userId)->pluck('auction_id')->toArray();
                $enrolledQuery = clone $query;
                $enrolledAuctions = $enrolledQuery->whereIn('id', $bidAuctionIds)->where('status', 'active')->orderBy('ends_at', 'asc')->get();
            }
            
            $transformAuction = function ($auction) {
                $auction->time_remaining = $auction->timeRemaining();
                $auction->bid_count = $auction->bidCount();
                $auction->current_price = $auction->current_price ?? $auction->starting_price;
                return $auction;
            };
            
            $myAuctions = $myAuctions->map($transformAuction);
            $enrolledAuctions = $enrolledAuctions->map($transformAuction);
            
            return response()->json([
                'my_auctions' => AuctionResource::collection($myAuctions),
                'enrolled_auctions' => AuctionResource::collection($enrolledAuctions),
            ]);
        }

        $query = $this->filterByRole($request, $query);

        if ($request->has('status')) {
            $statuses = $request->status;
            if (is_string($statuses)) {
                $statuses = explode(',', $statuses);
            }
            if (count($statuses) === 1) {
                $query->where('status', $statuses[0]);
            } else {
                $query->whereIn('status', $statuses);
            }
        } else {
            $query->where('status', 'active');
        }

        $auctions = $query->orderBy('ends_at', 'asc')->paginate(12);
        $auctions->getCollection()->transform(function ($auction) {
            $auction->time_remaining = $auction->timeRemaining();
            $auction->bid_count = $auction->bidCount();
            $auction->current_price = $auction->current_price ?? $auction->starting_price;
            return $auction;
        });
        return AuctionResource::collection($auctions);
    }

    public function myAuctions(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $query = Auction::with(['animal', 'owner', 'bids']);
        $query = $this->filterByRole($request, $query);

        $auctions = $query->orderBy('created_at', 'desc')->paginate(10);

        $auctions->getCollection()->transform(function ($auction) {
            $auction->time_remaining = $auction->timeRemaining();
            $auction->bid_count = $auction->bidCount();
            $auction->current_price = $auction->current_price ?? $auction->starting_price;
            return $auction;
        });

        return AuctionResource::collection($auctions);
    }

    public function myBids(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $userId = $this->getUserId($request);
        
        $bids = Bid::with(['auction.animal', 'auction.owner', 'user'])
            ->where('user_id', $userId)
            ->orderBy('bid_at', 'desc')
            ->paginate(10);

        return \App\Http\Resources\BidResource::collection($bids);
    }

    public function wonAuctions(Request $request): JsonResponse
    {
        $userId = $this->getUserId($request);
        
        $auctions = Auction::with(['animal', 'owner', 'winner'])
            ->where('winner_id', $userId)
            ->where('status', 'sold')
            ->orderBy('ended_at', 'desc')
            ->get();

        $auctions->transform(function ($auction) {
            $auction->time_remaining = $auction->timeRemaining();
            $auction->bid_count = $auction->bidCount();
            return $auction;
        });

        return response()->json([
            'data' => AuctionResource::collection($auctions),
        ]);
    }

    public function processStripePayment(Request $request, Auction $auction): JsonResponse
    {
        $userId = $this->getUserId($request);

        if ($auction->winner_id != $userId) {
            return response()->json(['message' => 'Only the winner can make payment'], 403);
        }

        if ($auction->payment_status !== 'pending') {
            return response()->json(['message' => 'Payment already processed or not in payment phase'], 400);
        }

        if ($auction->paymentExpired()) {
            return response()->json(['message' => 'Payment deadline has passed'], 400);
        }

        $validated = $request->validate([
            'payment_method_id' => 'required|string',
        ]);

        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $charge = \Stripe\Charge::create([
                'amount' => (int)($auction->current_price * 100),
                'currency' => 'sar',
                'payment_method' => $validated['payment_method_id'],
                'confirm' => true,
                'metadata' => [
                    'auction_id' => $auction->id,
                    'winner_id' => $userId,
                    'animal_id' => $auction->animal_id,
                ],
                'description' => "Payment for Auction #{$auction->id} - {$auction->title}",
            ]);

            if ($charge->status === 'succeeded') {
                $auction->update([
                    'payment_status' => 'verified',
                    'payment_verified_at' => now(),
                    'payment_proof_url' => $charge->receipt_url,
                    'payment_method' => 'stripe',
                    'stripe_charge_id' => $charge->id,
                ]);

                $animal = $auction->animal;
                if ($animal && $auction->winner_id) {
                    $animal->update(['owner_id' => $auction->winner_id]);

                    // Create OwnershipTransfer + OwnershipHistory (same as verifyPayment)
                    $commissionEnabled = Setting::getBoolean('transfer_commission_enabled', false);
                    $commissionType = Setting::get('transfer_commission_auction_type', Setting::get('transfer_commission_type', 'percentage'));
                    $commissionPercentage = (float) Setting::get('transfer_commission_auction_percentage', Setting::get('transfer_commission_percentage', 5));
                    $commissionFixed = (float) Setting::get('transfer_commission_auction_fixed', Setting::get('transfer_commission_fixed', 0));
                    $agreedPrice = $auction->current_price;
                    $commissionAmount = 0;
                    if ($commissionEnabled) {
                        $commissionAmount = $commissionType === 'percentage'
                            ? $agreedPrice * ($commissionPercentage / 100)
                            : $commissionFixed;
                    }

                    $transfer = OwnershipTransfer::create([
                        'from_user_id' => $auction->owner_id,
                        'to_user_id' => $auction->winner_id,
                        'status' => 'completed',
                        'transfer_type' => 'auction',
                        'reference_type' => 'auction',
                        'reference_id' => $auction->id,
                        'agreed_price' => $agreedPrice,
                        'commission_percentage' => $commissionEnabled ? $commissionPercentage : 0,
                        'commission_amount' => $commissionAmount,
                        'commission_paid' => false,
                        'completed_at' => now(),
                    ]);
                    $transfer->animals()->attach($auction->animal_id);

                    OwnershipHistory::create([
                        'animal_id' => $auction->animal_id,
                        'from_user_id' => $auction->owner_id,
                        'to_user_id' => $auction->winner_id,
                        'transfer_id' => $transfer->id,
                        'transfer_type' => 'auction',
                        'reference_type' => 'auction',
                        'reference_id' => $auction->id,
                        'commission_amount' => $commissionAmount,
                        'agreed_price' => $agreedPrice,
                        'created_at' => now(),
                    ]);

                    // Notify original owner
                    \App\Models\Notification::create([
                        'user_id' => $auction->owner_id,
                        'type' => 'auction_ownership_transferred',
                        'title' => 'Animal Sold',
                        'body' => "Your animal \"{$auction->title}\" has been sold and ownership transferred to the winning bidder.",
                        'data' => [
                            'auction_id' => $auction->id,
                            'link' => "/auctions/{$auction->id}",
                        ],
                    ]);

                    // Notify admins about pending commission
                    if ($commissionAmount > 0) {
                        $adminUsers = User::role('Admin')->get();
                        foreach ($adminUsers as $admin) {
                            \App\Models\Notification::create([
                                'user_id' => $admin->id,
                                'type' => 'auction_commission_due',
                                'title' => 'Commission Pending',
                                'body' => "Auction \"{$auction->title}\" completed — SAR {$commissionAmount} commission is pending.",
                                'data' => [
                                    'auction_id' => $auction->id,
                                    'transfer_id' => $transfer->id ?? null,
                                    'commission_amount' => $commissionAmount,
                                ],
                            ]);
                        }
                    }
                }

                // Notify winner
                if ($auction->winner_id) {
                    \App\Models\Notification::create([
                        'user_id' => $auction->winner_id,
                        'type' => 'auction_payment_verified',
                        'title' => 'Payment successful!',
                        'body' => "Your payment for \"{$auction->title}\" has been processed. The animal ownership has been transferred.",
                        'data' => [
                            'auction_id' => $auction->id,
                            'link' => "/auctions/{$auction->id}",
                        ],
                    ]);
                }

                return response()->json([
                    'message' => 'Payment successful!',
                    'data' => new AuctionResource($auction->load(['animal', 'owner', 'winner', 'bids'])),
                    'receipt_url' => $charge->receipt_url,
                ]);
            }

            return response()->json(['message' => 'Payment failed'], 400);
        } catch (\Stripe\Exception\CardException $e) {
            return response()->json([
                'message' => 'Card declined: ' . $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Payment failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'animal_id' => 'required|exists:animals,id',
            'title' => 'required|string|max:255',
            'starting_price' => 'required|numeric|min:1',
            'reserve_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'duration_hours' => 'required|integer|min:1|max:168',
        ]);

        $userId = $this->getUserId($request);
        $userRole = $this->getUserRole($request);
        
        if (!in_array($userRole, ['Admin', 'Owner', 'Manager'])) {
            return response()->json(['message' => 'Unauthorized to create auctions', 'error' => 'unauthorized'], 403);
        }

        $animal = Animal::findOrFail($validated['animal_id']);
        
        if (!$this->canCreateAuctionForAnimal($request, $animal)) {
            return response()->json(['message' => 'You can only auction animals you have access to', 'error' => 'unauthorized'], 403);
        }
        
        $ownerId = $userId;
        if ($userRole === 'Manager') {
            $user = $this->getUser($request);
            if ($user && $user->managed_by) {
                $ownerId = $user->managed_by;
            }
        }

        $autoApprove = Setting::getBoolean('auction_auto_approve', false);
        $status = $autoApprove ? 'active' : 'draft';

        $auction = Auction::create([
            'animal_id' => $validated['animal_id'],
            'owner_id' => $ownerId,
            'title' => $validated['title'],
            'starting_price' => $validated['starting_price'],
            'current_price' => $validated['starting_price'],
            'reserve_price' => $validated['reserve_price'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $status,
            'starts_at' => now(),
            'ends_at' => now()->addHours($validated['duration_hours']),
        ]);

        $auction->load(['animal', 'owner']);
        $auction->time_remaining = $auction->timeRemaining();
        $auction->bid_count = 0;

        $adminUsers = User::role('Admin')->get();
        $notificationBody = $autoApprove
            ? "A new auction \"{$auction->title}\" has been started by {$auction->owner->name}."
            : "A new auction \"{$auction->title}\" has been created by {$auction->owner->name} and is pending your approval.";
        foreach ($adminUsers as $admin) {
            \App\Models\Notification::create([
                'user_id' => $admin->id,
                'type' => 'auction_new',
                'title' => $autoApprove ? 'New auction created' : 'Auction pending approval',
                'body' => $notificationBody,
                'data' => [
                    'auction_id' => $auction->id,
                    'link' => "/auctions/{$auction->id}",
                ],
            ]);
        }

        if (!$autoApprove) {
            \App\Models\Notification::create([
                'user_id' => $auction->owner_id,
                'type' => 'auction_pending_approval',
                'title' => 'Auction submitted for approval',
                'body' => "Your auction \"{$auction->title}\" has been submitted for admin approval. You'll be notified once it's approved.",
                'data' => [
                    'auction_id' => $auction->id,
                    'link' => "/auctions/{$auction->id}",
                ],
            ]);
        }

        return response()->json([
            'message' => $autoApprove ? 'Auction created successfully' : 'Auction submitted for approval',
            'data' => new AuctionResource($auction),
        ], 201);
    }

    public function show(Request $request, Auction $auction)
    {
        if (!$this->canAccessAuction($request, $auction)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $auction->load(['animal', 'owner', 'bids.user']);
        $auction->time_remaining = $auction->timeRemaining();
        $auction->bid_count = $auction->bidCount();
        return new AuctionResource($auction);
    }

    public function update(Request $request, Auction $auction): JsonResponse
    {
        if (!$this->canModifyAuction($request, $auction)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        if ($auction->bidCount() > 0 && $auction->status === 'active') {
            return response()->json(['message' => 'Cannot modify auction with active bids'], 400);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'starting_price' => 'sometimes|numeric|min:1',
            'reserve_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:draft,active,ended,sold,cancelled',
            'duration_hours' => 'sometimes|integer|min:1|max:168',
        ]);

        if (isset($validated['duration_hours']) && $auction->status !== 'ended') {
            $validated['ends_at'] = now()->addHours($validated['duration_hours']);
        }

        if (isset($validated['starting_price'])) {
            $validated['current_price'] = $validated['starting_price'];
        }

        $auction->update($validated);

        $auction->load(['animal', 'owner', 'bids']);
        $auction->time_remaining = $auction->timeRemaining();
        $auction->bid_count = $auction->bidCount();

        return response()->json([
            'message' => 'Auction updated successfully',
            'data' => new AuctionResource($auction),
        ]);
    }

    public function disqualifyBidder(Request $request, Auction $auction, Bid $bid): JsonResponse
    {
        if (!$this->canModifyAuction($request, $auction)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        if ($bid->auction_id != $auction->id) {
            return response()->json(['message' => 'Bid does not belong to this auction'], 400);
        }

        $disqualifiedUserId = $bid->user_id;
        $wasWinning = $bid->is_winning;
        $bidAmount = $bid->amount;
        $bid->delete();

        if ($wasWinning) {
            $highestBid = $auction->bids()->orderBy('amount', 'desc')->first();
            if ($highestBid) {
                $highestBid->update(['is_winning' => true]);
                $auction->update(['current_price' => $highestBid->amount]);
            } else {
                $auction->update(['current_price' => $auction->starting_price]);
            }
        }

        \App\Models\Notification::create([
            'user_id' => $disqualifiedUserId,
            'type' => 'bidder_disqualified',
            'title' => 'You\'ve been disqualified',
            'body' => "Your bid of {$bidAmount} SAR on \"{$auction->title}\" has been disqualified.",
            'data' => [
                'auction_id' => $auction->id,
                'link' => "/auctions/{$auction->id}",
            ],
        ]);

        $auction->load(['animal', 'owner', 'bids']);
        $auction->time_remaining = $auction->timeRemaining();
        $auction->bid_count = $auction->bidCount();

        return response()->json([
            'message' => 'Bidder disqualified successfully',
            'data' => new AuctionResource($auction),
        ]);
    }

    public function destroy(Request $request, Auction $auction): JsonResponse
    {
        if (!$this->canModifyAuction($request, $auction)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        if ($auction->bidCount() > 0) {
            return response()->json(['message' => 'Cannot delete auction with bids'], 400);
        }

        $auction->delete();

        return response()->json(['message' => 'Auction deleted successfully']);
    }

    public function placeBid(Request $request, Auction $auction): JsonResponse
    {
        $minimumBid = $auction->current_price + 1;
        
        $validated = $request->validate([
            'amount' => "required|numeric|min:{$minimumBid}",
        ]);

        $userId = $this->getUserId($request);

        if ($auction->owner_id == $userId) {
            return response()->json(['message' => 'Cannot bid on your own auction'], 400);
        }

        if (!$auction->isActive()) {
            return response()->json(['message' => 'Auction is not active'], 400);
        }

        $user = $this->getUser($request);

        $previousHighestBid = $auction->highestBid();

        $bid = Bid::create([
            'auction_id' => $auction->id,
            'user_id' => $userId,
            'amount' => $validated['amount'],
            'bidder_name' => $user->name ?? 'Anonymous',
            'bid_at' => now(),
            'is_winning' => true,
        ]);

        if ($previousHighestBid) {
            $previousHighestBid->update(['is_winning' => false]);

            if ($previousHighestBid->user_id != $userId) {
                \App\Models\Notification::create([
                    'user_id' => $previousHighestBid->user_id,
                    'type' => 'auction_outbid',
                    'title' => 'You\'ve been outbid!',
                    'body' => "Someone placed a higher bid ({$bid->amount} SAR) on \"{$auction->title}\".",
                    'data' => [
                        'auction_id' => $auction->id,
                        'link' => "/auctions/{$auction->id}",
                    ],
                ]);

                $previousBidder = User::find($previousHighestBid->user_id);
                if ($previousBidder) {
                    $this->sendNotificationMail(
                        $previousBidder,
                        'auction_bid',
                        "You've Been Outbid – {$auction->title}",
                        [
                            "Someone placed a higher bid of {$bid->amount} SAR on \"{$auction->title}\".",
                            'Place a new bid to regain the winning position.',
                        ],
                        rtrim(env('FRONTEND_URL', config('app.url')), '/') . "/auctions/{$auction->id}",
                        'View Auction',
                    );
                }
            }
        }

        $auction->update(['current_price' => $validated['amount']]);
        $auction->load(['animal', 'owner', 'bids.user']);

        \App\Models\Notification::create([
            'user_id' => $auction->owner_id,
            'type' => 'auction_bid',
            'title' => 'New bid on your auction',
            'body' => "{$user->name} placed a bid of {$bid->amount} SAR on \"{$auction->title}\".",
            'data' => [
                'auction_id' => $auction->id,
                'bid_id' => $bid->id,
                'link' => "/auctions/{$auction->id}",
            ],
        ]);

        $auctionOwner = User::find($auction->owner_id);
        if ($auctionOwner) {
            $this->sendNotificationMail(
                $auctionOwner,
                'auction_bid',
                "New Bid on Your Auction – {$auction->title}",
                [
                    "{$user->name} placed a bid of {$bid->amount} SAR on \"{$auction->title}\".",
                    'Check your auction for the latest activity.',
                ],
                rtrim(env('FRONTEND_URL', config('app.url')), '/') . "/auctions/{$auction->id}",
                'View Auction',
            );
        }

        $auction->time_remaining = $auction->timeRemaining();
        $auction->bid_count = $auction->bidCount();

        return response()->json([
            'message' => 'Bid placed successfully',
            'data' => new BidResource($bid->load('user')),
            'auction' => new AuctionResource($auction),
        ], 201);
    }

    public function cancel(Request $request, Auction $auction): JsonResponse
    {
        if (!$this->canModifyAuction($request, $auction)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        if ($auction->status === 'sold') {
            return response()->json(['message' => 'Cannot cancel a sold auction'], 400);
        }

        $bidderIds = $auction->bids()->pluck('user_id')->unique()->toArray();

        $auction->update([
            'status' => 'cancelled',
            'ended_at' => now(),
        ]);

        foreach ($bidderIds as $bidderId) {
            \App\Models\Notification::create([
                'user_id' => $bidderId,
                'type' => 'auction_cancelled',
                'title' => 'Auction cancelled',
                'body' => "The auction \"{$auction->title}\" has been cancelled.",
                'data' => [
                    'auction_id' => $auction->id,
                ],
            ]);
        }

        $auction->load(['animal', 'owner', 'bids']);
        $auction->time_remaining = $auction->timeRemaining();
        $auction->bid_count = $auction->bidCount();

        return response()->json([
            'message' => 'Auction cancelled successfully',
            'data' => new AuctionResource($auction),
        ]);
    }

    public function endAuction(Request $request, Auction $auction): JsonResponse
    {
        if (!$this->canModifyAuction($request, $auction)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        if ($auction->status !== 'active') {
            return response()->json(['message' => 'Auction is not active'], 400);
        }

        $highestBid = $auction->highestBid();

        if ($highestBid) {
            $sold = $auction->reserve_price 
                ? $highestBid->amount >= $auction->reserve_price 
                : true;

            if ($sold) {
                $secondHighest = $auction->secondHighestBid();
                
                $paymentExpiryHours = (int) Setting::get('auction_payment_expiry_hours', 24);
                $secondWinnerEnabled = Setting::getBoolean('auction_second_winner_enabled', true);

                $auction->update([
                    'status' => 'sold',
                    'winner_id' => $highestBid->user_id,
                    'second_winner_id' => $secondWinnerEnabled ? $secondHighest?->user_id : null,
                    'ended_at' => now(),
                    'payment_expires_at' => now()->addHours($paymentExpiryHours),
                    'payment_status' => 'pending',
                ]);

                \App\Models\Notification::create([
                    'user_id' => $highestBid->user_id,
                    'type' => 'auction_won',
                    'title' => 'You won the auction!',
                    'body' => "Congratulations! You won \"{$auction->title}\" for {$highestBid->amount} SAR. Complete payment within {$paymentExpiryHours} hours.",
                    'data' => [
                        'auction_id' => $auction->id,
                        'link' => "/auctions/{$auction->id}",
                    ],
                ]);

                $winner = User::find($highestBid->user_id);
                if ($winner) {
                    $this->sendNotificationMail(
                        $winner,
                        'auction_won',
                        "Congratulations! You Won – {$auction->title}",
                        [
                            "You won \"{$auction->title}\" with a bid of {$highestBid->amount} SAR.",
                            "Please complete your payment within {$paymentExpiryHours} hours to secure the animal.",
                        ],
                        rtrim(env('FRONTEND_URL', config('app.url')), '/') . "/auctions/{$auction->id}",
                        'Complete Payment',
                    );
                }
            } else {
                $auction->update([
                    'status' => 'ended',
                    'ended_at' => now(),
                ]);

                \App\Models\Notification::create([
                    'user_id' => $auction->owner_id,
                    'type' => 'auction_ended',
                    'title' => 'Auction ended - reserve not met',
                    'body' => "Your auction \"{$auction->title}\" ended without meeting the reserve price.",
                    'data' => [
                        'auction_id' => $auction->id,
                        'link' => "/auctions/{$auction->id}",
                    ],
                ]);

                $owner = User::find($auction->owner_id);
                if ($owner) {
                    $this->sendNotificationMail(
                        $owner,
                        'auction_bid',
                        "Auction Ended – Reserve Not Met – {$auction->title}",
                        [
                            "Your auction \"{$auction->title}\" has ended.",
                            'The highest bid did not meet the reserve price. No sale was made.',
                        ],
                        rtrim(env('FRONTEND_URL', config('app.url')), '/') . "/auctions/{$auction->id}",
                        'View Auction',
                    );
                }
            }
        } else {
            $auction->update([
                'status' => 'ended',
                'ended_at' => now(),
            ]);

            \App\Models\Notification::create([
                'user_id' => $auction->owner_id,
                'type' => 'auction_ended',
                'title' => 'Auction ended - no bids',
                'body' => "Your auction \"{$auction->title}\" ended with no bids placed.",
                'data' => [
                    'auction_id' => $auction->id,
                    'link' => "/auctions/{$auction->id}",
                ],
            ]);

            $owner = User::find($auction->owner_id);
            if ($owner) {
                $this->sendNotificationMail(
                    $owner,
                    'auction_bid',
                    "Auction Ended – No Bids – {$auction->title}",
                    [
                        "Your auction \"{$auction->title}\" has ended with no bids placed.",
                        'You can relist the auction or adjust the pricing.',
                    ],
                    rtrim(env('FRONTEND_URL', config('app.url')), '/') . "/auctions/{$auction->id}",
                    'View Auction',
                );
            }
        }

        $auction->load(['animal', 'owner', 'winner', 'secondWinner', 'bids']);
        $auction->time_remaining = $auction->timeRemaining();
        $auction->bid_count = $auction->bidCount();

        return response()->json([
            'message' => 'Auction ended',
            'data' => new AuctionResource($auction),
        ]);
    }

    public function uploadPaymentProof(Request $request, Auction $auction): JsonResponse
    {
        $userId = $this->getUserId($request);

        if ($auction->winner_id != $userId) {
            return response()->json(['message' => 'Only the winner can upload payment proof'], 403);
        }

        if ($auction->payment_status !== 'pending') {
            return response()->json(['message' => 'Payment proof already uploaded or auction not in payment phase'], 400);
        }

        if ($auction->paymentExpired()) {
            return response()->json(['message' => 'Payment deadline has passed'], 400);
        }

        $validated = $request->validate([
            'payment_proof' => 'required|file|mimes:pdf|max:10240',
        ]);

        $path = $request->file('payment_proof')->store('payment-proofs', 'public');
        $url = asset('storage/' . $path);

        $auction->update([
            'payment_proof_url' => $url,
            'payment_status' => 'submitted',
        ]);

        $adminUsers = User::role('Admin')->get();
        foreach ($adminUsers as $admin) {
            \App\Models\Notification::create([
                'user_id' => $admin->id,
                'type' => 'payment_proof_submitted',
                'title' => 'Payment proof submitted',
                'body' => "Payment proof has been uploaded for auction \"{$auction->title}\" by {$user->name}.",
                'data' => [
                    'auction_id' => $auction->id,
                    'link' => "/auctions/{$auction->id}",
                ],
            ]);
        }

        return response()->json([
            'message' => 'Payment proof uploaded successfully',
            'data' => new AuctionResource($auction->load(['animal', 'owner', 'winner', 'secondWinner', 'bids'])),
        ]);
    }

    public function verifyPayment(Request $request, Auction $auction, string $status): JsonResponse
    {
        $userId = $this->getUserId($request);
        $userRole = $this->getUserRole($request);

        if ($userRole !== 'Admin') {
            return response()->json(['message' => 'Only admins can verify payments'], 403);
        }

        if (!in_array($status, ['approved', 'rejected'])) {
            return response()->json(['message' => 'Invalid status'], 400);
        }

        if ($auction->payment_status !== 'submitted') {
            return response()->json(['message' => 'No payment proof submitted'], 400);
        }

        $notes = $request->input('notes');

        if ($status === 'approved') {
            $auction->update([
                'payment_status' => 'verified',
                'payment_verified_at' => now(),
                'verified_by' => $userId,
                'payment_notes' => $notes,
            ]);
            
            $animal = $auction->animal;
            if ($animal && $auction->winner_id) {
                $commissionEnabled = Setting::getBoolean('transfer_commission_enabled', false);
                $commissionType = Setting::get('transfer_commission_auction_type', Setting::get('transfer_commission_type', 'percentage'));
                $commissionPercentage = (float) Setting::get('transfer_commission_auction_percentage', Setting::get('transfer_commission_percentage', 5));
                $commissionFixed = (float) Setting::get('transfer_commission_auction_fixed', Setting::get('transfer_commission_fixed', 0));
                $agreedPrice = $auction->current_price;
                $commissionAmount = 0;
                if ($commissionEnabled) {
                    $commissionAmount = $commissionType === 'percentage'
                        ? $agreedPrice * ($commissionPercentage / 100)
                        : $commissionFixed;
                }

                $transfer = OwnershipTransfer::create([
                    'from_user_id' => $auction->owner_id,
                    'to_user_id' => $auction->winner_id,
                    'status' => 'completed',
                    'transfer_type' => 'auction',
                    'reference_type' => 'auction',
                    'reference_id' => $auction->id,
                    'agreed_price' => $agreedPrice,
                    'commission_percentage' => $commissionEnabled ? $commissionPercentage : 0,
                    'commission_amount' => $commissionAmount,
                    'commission_paid' => false,
                    'completed_at' => now(),
                ]);
                $transfer->animals()->attach($auction->animal_id);

                OwnershipHistory::create([
                    'animal_id' => $auction->animal_id,
                    'from_user_id' => $auction->owner_id,
                    'to_user_id' => $auction->winner_id,
                    'transfer_id' => $transfer->id,
                    'transfer_type' => 'auction',
                    'reference_type' => 'auction',
                    'reference_id' => $auction->id,
                    'commission_amount' => $commissionAmount,
                    'agreed_price' => $agreedPrice,
                    'created_at' => now(),
                ]);

                $animal->update([
                    'owner_id' => $auction->winner_id,
                ]);

                // Notify original owner
                \App\Models\Notification::create([
                    'user_id' => $auction->owner_id,
                    'type' => 'auction_ownership_transferred',
                    'title' => 'Animal Sold',
                    'body' => "Your animal \"{$auction->title}\" has been sold and ownership transferred to the winning bidder.",
                    'data' => [
                        'auction_id' => $auction->id,
                        'link' => "/auctions/{$auction->id}",
                    ],
                ]);

                // Notify admins about pending commission
                if ($commissionAmount > 0) {
                    $adminUsers = User::role('Admin')->get();
                    foreach ($adminUsers as $admin) {
                        \App\Models\Notification::create([
                            'user_id' => $admin->id,
                            'type' => 'auction_commission_due',
                            'title' => 'Commission Pending',
                            'body' => "Auction \"{$auction->title}\" completed — SAR {$commissionAmount} commission is pending.",
                            'data' => [
                                'auction_id' => $auction->id,
                                'transfer_id' => $transfer->id,
                                'commission_amount' => $commissionAmount,
                            ],
                        ]);
                    }
                }
            }

            if ($auction->winner_id) {
                \App\Models\Notification::create([
                    'user_id' => $auction->winner_id,
                    'type' => 'auction_payment_verified',
                    'title' => 'Payment verified!',
                    'body' => "Your payment for \"{$auction->title}\" has been verified. The animal ownership has been transferred.",
                    'data' => [
                        'auction_id' => $auction->id,
                        'link' => "/auctions/{$auction->id}",
                    ],
                ]);

                $winner = User::find($auction->winner_id);
                if ($winner) {
                    $this->sendNotificationMail(
                        $winner,
                        'auction_payment',
                        "Payment Verified – {$auction->title}",
                        [
                            "Your payment for \"{$auction->title}\" has been verified.",
                            'The ownership of the animal has been transferred to you.',
                        ],
                        rtrim(env('FRONTEND_URL', config('app.url')), '/') . "/auctions/{$auction->id}",
                        'View Auction',
                    );
                }
            }
        } else {
            $paymentExpiryHours = (int) Setting::get('auction_payment_expiry_hours', 24);
            $auction->update([
                'payment_status' => 'rejected',
                'verified_by' => $userId,
                'payment_notes' => $notes,
                'payment_expires_at' => now()->addHours($paymentExpiryHours),
            ]);

            if ($auction->winner_id) {
                $oldWinner = User::find($auction->winner_id);

                \App\Models\Notification::create([
                    'user_id' => $auction->winner_id,
                    'type' => 'auction_payment_rejected',
                    'title' => 'Payment rejected',
                    'body' => 'Your payment proof for "' . $auction->title . '" was rejected.' . ($notes ? " Reason: {$notes}" : ''),
                    'data' => [
                        'auction_id' => $auction->id,
                        'link' => "/auctions/{$auction->id}",
                    ],
                ]);

                if ($oldWinner) {
                    $this->sendNotificationMail(
                        $oldWinner,
                        'auction_payment',
                        "Payment Rejected – {$auction->title}",
                        [
                            'Your payment proof for "' . $auction->title . '" was rejected.' . ($notes ? " Reason: {$notes}" : ''),
                            'Please upload a valid payment proof within {$paymentExpiryHours} hours.',
                        ],
                        rtrim(env('FRONTEND_URL', config('app.url')), '/') . "/auctions/{$auction->id}",
                        'View Auction',
                    );
                }
            }

            $secondWinnerEnabled = Setting::getBoolean('auction_second_winner_enabled', true);
            $secondWinner = $secondWinnerEnabled ? $auction->secondWinner : null;
            if ($secondWinner) {
                $auction->update([
                    'winner_id' => $secondWinner->id,
                    'second_winner_id' => null,
                    'payment_status' => 'pending',
                ]);

                \App\Models\Notification::create([
                    'user_id' => $secondWinner->id,
                    'type' => 'auction_won',
                    'title' => 'You\'re the new winner!',
                    'body' => "The previous winner didn't complete payment. You've been promoted to winner for \"{$auction->title}\".",
                    'data' => [
                        'auction_id' => $auction->id,
                        'link' => "/auctions/{$auction->id}",
                    ],
                ]);

                $this->sendNotificationMail(
                    $secondWinner,
                    'auction_won',
                    "You're the New Winner! – {$auction->title}",
                    [
                        "The previous winner didn't complete payment. You've been promoted to winner for \"{$auction->title}\".",
                        "Please complete your payment within {$paymentExpiryHours} hours.",
                    ],
                    rtrim(env('FRONTEND_URL', config('app.url')), '/') . "/auctions/{$auction->id}",
                    'Complete Payment',
                );
            }
        }

        return response()->json([
            'message' => 'Payment ' . ($status === 'approved' ? 'verified' : 'rejected'),
            'data' => new AuctionResource($auction->load(['animal', 'owner', 'winner', 'secondWinner', 'verifier', 'bids'])),
        ]);
    }

    public function processExpiredPayments(): JsonResponse
    {
        $processed = AuctionPaymentService::processExpiredPayments();

        return response()->json([
            'message' => "Processed {$processed} expired payments",
        ]);
    }

    public function adminPendingApproval(Request $request): JsonResponse
    {
        $auctions = Auction::with(['animal', 'owner'])
            ->where('status', 'draft')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $auctions->getCollection()->transform(function ($auction) {
            $auction->current_price = $auction->current_price ?? $auction->starting_price;
            return $auction;
        });

        return response()->json([
            'data' => AuctionResource::collection($auctions),
        ]);
    }

    public function adminApprove(Request $request, Auction $auction): JsonResponse
    {
        if ($auction->status !== 'draft') {
            return response()->json(['message' => 'Only draft auctions can be approved'], 400);
        }

        $originalDuration = $auction->starts_at ? max(1, $auction->starts_at->diffInHours($auction->ends_at)) : 48;

        $auction->update([
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addHours($originalDuration),
        ]);

        \App\Models\Notification::create([
            'user_id' => $auction->owner_id,
            'type' => 'auction_approved',
            'title' => 'Auction approved!',
            'body' => "Your auction \"{$auction->title}\" has been approved and is now live for bidding.",
            'data' => [
                'auction_id' => $auction->id,
                'link' => "/auctions/{$auction->id}",
            ],
        ]);

        $owner = User::find($auction->owner_id);
        if ($owner) {
            $this->sendNotificationMail(
                $owner,
                'auction_bid',
                "Auction Approved – {$auction->title}",
                [
                    "Your auction \"{$auction->title}\" has been approved and is now live.",
                    'Bidders can now place their bids on your animal.',
                ],
                rtrim(env('FRONTEND_URL', config('app.url')), '/') . "/auctions/{$auction->id}",
                'View Auction',
            );
        }

        $auction->load(['animal', 'owner']);
        $auction->time_remaining = $auction->timeRemaining();
        $auction->bid_count = $auction->bidCount();

        return response()->json([
            'message' => 'Auction approved successfully',
            'data' => new AuctionResource($auction),
        ]);
    }

    public function adminReject(Request $request, Auction $auction): JsonResponse
    {
        if ($auction->status !== 'draft') {
            return response()->json(['message' => 'Only draft auctions can be rejected'], 400);
        }

        $notes = $request->input('notes');

        $auction->update([
            'status' => 'cancelled',
            'payment_notes' => $notes,
            'ended_at' => now(),
        ]);

        \App\Models\Notification::create([
            'user_id' => $auction->owner_id,
            'type' => 'auction_rejected',
            'title' => 'Auction rejected',
            'body' => 'Your auction "' . $auction->title . '" was not approved.' . ($notes ? " Reason: {$notes}" : ''),
            'data' => [
                'auction_id' => $auction->id,
                'link' => "/auctions/{$auction->id}",
            ],
        ]);

        $owner = User::find($auction->owner_id);
        if ($owner) {
            $this->sendNotificationMail(
                $owner,
                'auction_bid',
                "Auction Not Approved – {$auction->title}",
                [
                    'Your auction "' . $auction->title . '" was not approved.' . ($notes ? " Reason: {$notes}" : ''),
                    'Please review and resubmit if needed.',
                ],
                rtrim(env('FRONTEND_URL', config('app.url')), '/') . "/auctions/{$auction->id}",
                'View Auction',
            );
        }

        $auction->load(['animal', 'owner']);

        return response()->json([
            'message' => 'Auction rejected',
            'data' => new AuctionResource($auction),
        ]);
    }

    public function adminPayments(Request $request): JsonResponse
    {
        $query = Auction::with(['animal', 'owner', 'winner', 'verifier'])
            ->whereIn('status', ['sold', 'ended'])
            ->whereNotNull('payment_status');

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $auctions = $query->orderBy('updated_at', 'desc')->paginate(20);

        $auctions->getCollection()->transform(function ($auction) {
            $auction->current_price = $auction->current_price ?? $auction->starting_price;
            return $auction;
        });

        return response()->json([
            'data' => AuctionResource::collection($auctions),
        ]);
    }
}