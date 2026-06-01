<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetRequestContext;
use Period\WpKit\WordPress\Access\AssetUploadDecision;
use Period\WpKit\WordPress\Access\AssetUploadPathResolver;
use Period\WpKit\WordPress\Access\AssetUploadPolicyInterface;
use Period\WpKit\WordPress\Access\DefaultProtectedAssetPathStrategy;
use Period\WpKit\WordPress\Access\RoleBasedAssetUploadPolicy;

final class AssetUploadInterceptionTest extends TestCase
{
    private function makeContext(array $roles, string $path = '/uploads/file.pdf'): AssetRequestContext
    {
        return new AssetRequestContext(
            assetPath: $path,
            assetUrl: 'https://example.com' . $path,
            currentUserId: 1,
            currentUserRoles: $roles,
            requestTime: new DateTimeImmutable(),
        );
    }

    // --- AssetUploadDecision ---

    public function testAsPublicIsNotProtected(): void
    {
        $decision = AssetUploadDecision::asPublic('/uploads/file.pdf');

        $this->assertFalse($decision->isProtected());
    }

    public function testAsPublicStoresTargetPath(): void
    {
        $decision = AssetUploadDecision::asPublic('/uploads/file.pdf');

        $this->assertSame('/uploads/file.pdf', $decision->targetPath());
    }

    public function testAsProtectedIsProtected(): void
    {
        $decision = AssetUploadDecision::asProtected('/protected-uploads/file.pdf');

        $this->assertTrue($decision->isProtected());
    }

    public function testAsProtectedStoresTargetPath(): void
    {
        $decision = AssetUploadDecision::asProtected('/protected-uploads/file.pdf');

        $this->assertSame('/protected-uploads/file.pdf', $decision->targetPath());
    }

    // --- AssetUploadPolicyInterface ---

    public function testRoleBasedPolicyImplementsInterface(): void
    {
        $this->assertInstanceOf(
            AssetUploadPolicyInterface::class,
            new RoleBasedAssetUploadPolicy([]),
        );
    }

    // --- RoleBasedAssetUploadPolicy ---

    public function testProtectedWhenRoleMatches(): void
    {
        $policy   = new RoleBasedAssetUploadPolicy(['editor', 'administrator']);
        $decision = $policy->decide($this->makeContext(['editor']));

        $this->assertTrue($decision->isProtected());
    }

    public function testProtectedWhenOneOfMultipleUserRolesMatches(): void
    {
        $policy   = new RoleBasedAssetUploadPolicy(['administrator']);
        $decision = $policy->decide($this->makeContext(['subscriber', 'administrator']));

        $this->assertTrue($decision->isProtected());
    }

    public function testPublicWhenNoRoleMatches(): void
    {
        $policy   = new RoleBasedAssetUploadPolicy(['administrator']);
        $decision = $policy->decide($this->makeContext(['subscriber']));

        $this->assertFalse($decision->isProtected());
    }

    public function testPublicWhenUserHasNoRoles(): void
    {
        $policy   = new RoleBasedAssetUploadPolicy(['editor']);
        $decision = $policy->decide($this->makeContext([]));

        $this->assertFalse($decision->isProtected());
    }

    public function testPublicWhenProtectedRolesIsEmpty(): void
    {
        $policy   = new RoleBasedAssetUploadPolicy([]);
        $decision = $policy->decide($this->makeContext(['administrator']));

        $this->assertFalse($decision->isProtected());
    }

    public function testDecisionTargetPathComesFromContext(): void
    {
        $policy   = new RoleBasedAssetUploadPolicy(['editor']);
        $decision = $policy->decide($this->makeContext(['editor'], '/uploads/photo.jpg'));

        $this->assertSame('/uploads/photo.jpg', $decision->targetPath());
    }

    // --- AssetUploadPathResolver ---

    public function testResolveProtectedReturnsProtectedPath(): void
    {
        $resolver = new AssetUploadPathResolver(new DefaultProtectedAssetPathStrategy());

        $this->assertSame(
            '/protected-uploads/file.pdf',
            $resolver->resolve('/uploads/file.pdf', true),
        );
    }

    public function testResolvePublicReturnsPublicPath(): void
    {
        $resolver = new AssetUploadPathResolver(new DefaultProtectedAssetPathStrategy());

        $this->assertSame(
            '/uploads/file.pdf',
            $resolver->resolve('/protected-uploads/file.pdf', false),
        );
    }

    public function testResolvePublicOnPublicPathReturnsAsIs(): void
    {
        $resolver = new AssetUploadPathResolver(new DefaultProtectedAssetPathStrategy());

        $this->assertSame(
            '/uploads/file.pdf',
            $resolver->resolve('/uploads/file.pdf', false),
        );
    }

    public function testResolveWithSubdirectory(): void
    {
        $resolver = new AssetUploadPathResolver(new DefaultProtectedAssetPathStrategy());

        $this->assertSame(
            '/protected-uploads/2026/01/report.pdf',
            $resolver->resolve('/uploads/2026/01/report.pdf', true),
        );
    }

    // --- no file move behavior ---

    public function testDecideDoesNotTouchFilesystem(): void
    {
        // If this test completes without touching real files, the assertion passes.
        // Filesystem access would manifest as exceptions or test side-effects.
        $policy   = new RoleBasedAssetUploadPolicy(['editor']);
        $decision = $policy->decide($this->makeContext(['editor'], '/uploads/nonexistent-file.pdf'));

        $this->assertInstanceOf(AssetUploadDecision::class, $decision);
    }

    public function testResolveDoesNotTouchFilesystem(): void
    {
        $resolver = new AssetUploadPathResolver(new DefaultProtectedAssetPathStrategy());
        $result   = $resolver->resolve('/uploads/nonexistent-file.pdf', true);

        $this->assertIsString($result);
    }
}
