<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Portfolio;
use App\Models\PortfolioImage;
use App\Models\PortfolioSkill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PortfolioController extends Controller
{
    use ApiResponse;

    /**
     * List own portfolios.
     */
    public function index(Request $request): JsonResponse
    {
        $portfolios = Portfolio::where('user_id', $request->user()->id)
            ->with(['images', 'skills'])
            ->latest()
            ->get()
            ->map(fn ($p) => $this->formatPortfolio($p));

        return $this->successResponse($portfolios, 'Portfolios retrieved');
    }

    /**
     * Create a new portfolio entry.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'external_link' => ['nullable', 'url', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
            'skills' => ['required', 'array', 'min:1'],
            'skills.*' => ['string', 'max:50'],
            'images' => ['nullable', 'array', 'max:2'],
            'images.*' => ['image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();

        $portfolio = Portfolio::create([
            'user_id' => $user->id,
            'description' => $request->description,
            'external_link' => $request->external_link,
            'is_public' => $request->input('is_public', true),
        ]);

        // Save skills
        foreach ($request->skills as $skill) {
            PortfolioSkill::create([
                'portfolio_id' => $portfolio->id,
                'skill_name' => $skill,
            ]);
        }

        // Save images (max 2)
        if ($request->hasFile('images')) {
            $order = 1;
            foreach ($request->file('images') as $image) {
                if ($order > 2) break;

                $path = $image->store("portfolios/{$portfolio->id}", 'public');
                PortfolioImage::create([
                    'portfolio_id' => $portfolio->id,
                    'image_path' => $path,
                    'order' => $order++,
                ]);
            }
        }

        $portfolio->load(['images', 'skills']);

        return $this->createdResponse(
            $this->formatPortfolio($portfolio),
            'Portfolio created successfully'
        );
    }

    /**
     * Update a portfolio.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $portfolio = Portfolio::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $request->validate([
            'description' => ['sometimes', 'string', 'min:10', 'max:2000'],
            'external_link' => ['nullable', 'url', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
            'skills' => ['sometimes', 'array', 'min:1'],
            'skills.*' => ['string', 'max:50'],
            'images' => ['nullable', 'array', 'max:2'],
            'images.*' => ['image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $portfolio->update($request->only([
            'description', 'external_link', 'is_public',
        ]));

        // Replace skills if provided
        if ($request->has('skills')) {
            $portfolio->skills()->delete();
            foreach ($request->skills as $skill) {
                PortfolioSkill::create([
                    'portfolio_id' => $portfolio->id,
                    'skill_name' => $skill,
                ]);
            }
        }

        // Replace images if provided
        if ($request->hasFile('images')) {
            // Delete old images
            foreach ($portfolio->images as $oldImage) {
                Storage::disk('public')->delete($oldImage->image_path);
                $oldImage->delete();
            }

            $order = 1;
            foreach ($request->file('images') as $image) {
                if ($order > 2) break;

                $path = $image->store("portfolios/{$portfolio->id}", 'public');
                PortfolioImage::create([
                    'portfolio_id' => $portfolio->id,
                    'image_path' => $path,
                    'order' => $order++,
                ]);
            }
        }

        $portfolio->load(['images', 'skills']);

        return $this->successResponse(
            $this->formatPortfolio($portfolio),
            'Portfolio updated successfully'
        );
    }

    /**
     * Delete a portfolio.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $portfolio = Portfolio::where('user_id', $request->user()->id)
            ->findOrFail($id);

        // Delete images from storage
        foreach ($portfolio->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $portfolio->delete();

        return $this->successResponse(null, 'Portfolio deleted successfully');
    }

    /**
     * Toggle portfolio public/private.
     */
    public function toggleVisibility(Request $request, int $id): JsonResponse
    {
        $portfolio = Portfolio::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $portfolio->update(['is_public' => !$portfolio->is_public]);

        return $this->successResponse([
            'id' => $portfolio->id,
            'is_public' => $portfolio->is_public,
        ], 'Portfolio visibility updated');
    }

    /**
     * Format portfolio for response.
     */
    protected function formatPortfolio(Portfolio $portfolio): array
    {
        return [
            'id' => $portfolio->id,
            'description' => $portfolio->description,
            'external_link' => $portfolio->external_link,
            'is_public' => $portfolio->is_public,
            'skills' => $portfolio->skills->pluck('skill_name')->toArray(),
            'images' => $portfolio->images->map(fn ($i) => Storage::disk('public')->url($i->image_path))->toArray(),
            'created_at' => $portfolio->created_at,
        ];
    }
}
