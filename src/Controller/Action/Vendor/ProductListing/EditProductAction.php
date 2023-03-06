<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Controller\Action\Vendor\ProductListing;

use BitBag\OpenMarketplace\Entity\ProductListing\ProductDraftInterface;
use BitBag\OpenMarketplace\Factory\ProductListingFromDraftFactoryInterface;
use BitBag\OpenMarketplace\Form\ProductListing\ProductType;
use BitBag\OpenMarketplace\Repository\ProductListing\ProductDraftRepositoryInterface;
use BitBag\OpenMarketplace\Repository\ProductListing\ProductListingRepositoryInterface;
use BitBag\OpenMarketplace\Security\Voter\ObjectOwningVoter;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactoryInterface;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Sylius\Component\Resource\Metadata\MetadataInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

final class EditProductAction
{
    public function __construct(
        private MetadataInterface $metadata,
        private RequestConfigurationFactoryInterface $requestConfigurationFactory,
        private ProductDraftRepositoryInterface $productDraftRepository,
        private ProductListingFromDraftFactoryInterface $productListingFromDraftFactory,
        private ImageUploaderInterface $imageUploader,
        private ProductListingRepositoryInterface $productListingRepository,
        private AuthorizationCheckerInterface $authorizationChecker,
        private FormFactoryInterface $formFactory,
        private RequestStack $requestStack,
        private Environment $twig,
        ) {
    }

    public function __invoke(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $listing = $this->productListingRepository->find($request->get('id'));

        if (!$this->authorizationChecker->isGranted(ObjectOwningVoter::OWNIT, $listing)) {
            throw new AccessDeniedException();
        }
        /** @var ProductDraftInterface $newResource */
        $newResource = $this->productDraftRepository->findLatestDraft($listing);

        if (!(ProductDraftInterface::STATUS_CREATED === $newResource->getStatus())) {
            $newResource = $this->productListingFromDraftFactory->createClone($newResource);
            $newResource->getProductListing()->setVerificationStatus(ProductDraftInterface::STATUS_CREATED);
        }

        $form = $this->formFactory->create(ProductType::class, $newResource);

        $form->handleRequest($request);
        if ($request->isMethod('POST') && $form->isSubmitted() && $form->isValid()) {
            /** @var ProductDraftInterface $productDraft */
            $productDraft = $form->getData();

            foreach ($productDraft->getImages() as $image) {
                $image->setOwner($newResource);
                $this->imageUploader->upload($image);
            }
            foreach ($productDraft->getAttributes() as $attribute) {
                $attribute->setSubject($productDraft);
                $productDraft->addAttribute($attribute);
            }

            $productDraft = $this->productListingFromDraftFactory->saveEdit($productDraft);

            $this->productDraftRepository->save($productDraft);
            /** @var Session $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add('success', 'open_marketplace.ui.product_listing_saved');
        }

        return new Response(
            $this->twig->render('Vendor/ProductListing/update_form.html.twig', [
                'configuration' => $configuration,
                'metadata' => $this->metadata,
                'resource' => $newResource,
                $this->metadata->getName() => $newResource,
                'form' => $form->createView(),
            ])
        );
    }
}
