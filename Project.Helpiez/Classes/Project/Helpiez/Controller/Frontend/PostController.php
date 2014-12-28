<?php
/**
 * Created by PhpStorm.
 * User: garvit
 * Date: 27/12/14
 * Time: 12:17 AM
 */

namespace Project\Helpiez\Controller\Frontend;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;

class PostController extends ActionController {

	/**
	 * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
	 * @Flow\Inject
	 */
	protected $authenticationManager;

	/**
	 * @var \TYPO3\Flow\Security\Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	/**
	 * @var \Project\Helpiez\Domain\Repository\FollowerRepository
	 * @Flow\Inject
	 */
	protected $followerRepository;

	/**
	 * @var \Project\Helpiez\Domain\Repository\RatingRepository
	 * @Flow\Inject
	 */
	protected $ratingRepository;

	/**
	 * @var \Project\Helpiez\Domain\Repository\ReviewRepository
	 * @Flow\Inject
	 */
	protected $reviewRepository;

	/**
	 * @var \Project\Helpiez\Domain\Repository\UserAccountRepository
	 * @Flow\Inject
	 */
	protected $userAccountRepository;

	/**
	 * @var \Project\Helpiez\Domain\Repository\OrganisationRepository
	 * @Flow\Inject
	 */
	protected $organisationRepository;

	/**
	 * @var \Project\Helpiez\Domain\Repository\PendingOrganisationRepository
	 * @Flow\Inject
	 */
	protected $pendingOrganisationRepository;

	/**
	 * @var \Project\Helpiez\Domain\Model\Follower
	 * @Flow\Inject
	 */
	protected $follower;

	/**
	 * @var \Project\Helpiez\Domain\Model\Rating
	 * @Flow\Inject
	 */
	protected $rating;

	/**
	 * @var \Project\Helpiez\Domain\Model\Review
	 * @Flow\Inject
	 */
	protected $review;

	/**
	 * @param string $organisationName
	 * @return string
	 */
	public function followOrganisationAction($organisationName) {
		$organisationName = str_replace("_", " ", $organisationName);
		if(!$this->authenticationManager->isAuthenticated()) {
			return "false";
		}
		$account = $this->securityContext->getAccount();
		$username = $account->getAccountIdentifier();

		/**
		 * looking for userAccount of loggedin user
		 */
		$query = $this->userAccountRepository->createQuery();
		$query->matching(
			$query->equals('username', $username)
		);
		$result = $query->execute();
		if($result->count() < 1) {
			return "false";
		}
		$userAccount = $result->getFirst();
		$this->follower->setUserAccount($userAccount);

		/**
		 * getting the organisation from it's name
		 */
		$query = $this->organisationRepository->createQuery();
		$query->matching(
			$query->equals('name', $organisationName)
		);
		$result = $query->execute();
		if($result->count() < 1) {
			return "false";
		}
		$organisation= $result->getFirst();
		$this->follower->setOrganisation($organisation);

		/**
		 * if follower exists return false
		 */
		$query = $this->followerRepository->createQuery();
		$query->matching(
			$query->logicalAnd(
				$query->equals('userAccount', $userAccount),
				$query->equals('organisation', $organisation)
			)
		);
		$result = $query->execute();
		if($result->count() == 1) {
			return "false";
		}

		/**
		 * add follower
		 */
		$this->followerRepository->add($this->follower);
		return "true";
	}

	/**
	 * @param string $organisationName
	 * @param int $rate
	 * @return string
	 */
	public function rateOrganisationAction($organisationName, $rate){
		$organisationName = str_replace("_", " ", $organisationName);
		if(!$this->authenticationManager->isAuthenticated()) {
			return "false";
		}
		$account = $this->securityContext->getAccount();
		$username = $account->getAccountIdentifier();

		/**
		 * looking for userAccount of loggedin user
		 */
		$query = $this->userAccountRepository->createQuery();
		$query->matching(
			$query->equals('username', $username)
		);
		$result = $query->execute();
		if($result->count() < 1) {
			return "false";
		}
		$userAccount = $result->getFirst();
		$this->rating->setUserAccount($userAccount);

		/**
		 * getting the organisation from it's name
		 */
		$query = $this->organisationRepository->createQuery();
		$query->matching(
			$query->equals('name', $organisationName)
		);
		$result = $query->execute();
		if($result->count() < 1) {
			return "false";
		}
		$organisation= $result->getFirst();
		$this->rating->setOrganisation($organisation);

		$this->rating->setRating($rate);

		/**
		 * if rating exists update rating
		 */
		$query = $this->ratingRepository->createQuery();
		$query->matching(
			$query->logicalAnd(
				$query->equals('userAccount', $userAccount),
				$query->equals('organisation', $organisation)
			)
		);
		$result = $query->execute();
		if($result->count() == 1) {
			$oldRating = $result->getFirst();
			$oldRating->setRating($rate);
			$this->ratingRepository->update($oldRating);
			return "true";
		}

		/**
		 * else add rating
		 */
		$this->ratingRepository->add($this->rating);

		$query = $this->ratingRepository->createQuery();
		$query->matching(
			$query->equals('organisation', $organisation)
		);
		$result = $query->execute();
		$count = $result->count();

		$rating = ($organisation->getRating() * $count + $rate ) / ( $count + 1 );

		$organisation->setRating($rating);

		$this->organisationRepository->update($organisation);

		return "true";
	}

	/**
	 * @param string $organisationName
	 * @param string $review
	 * @return string
	 */
	public function reviewOrganisationAction($organisationName, $review) {
		$organisationName = str_replace("_", " ", $organisationName);
		if(!$this->authenticationManager->isAuthenticated()) {
			return "false";
		}
		$account = $this->securityContext->getAccount();
		$username = $account->getAccountIdentifier();

		/**
		 * looking for userAccount of loggedin user
		 */
		$query = $this->userAccountRepository->createQuery();
		$query->matching(
			$query->equals('username', $username)
		);
		$result = $query->execute();
		if($result->count() < 1) {
			return "false";
		}
		$userAccount = $result->getFirst();
		$this->review->setUserAccount($userAccount);

		/**
		 * getting the organisation with it's name
		 */
		$query = $this->organisationRepository->createQuery();
		$query->matching(
			$query->equals('name', $organisationName)
		);
		$result = $query->execute();
		if($result->count() < 1) {
			return "false";
		}
		$organisation= $result->getFirst();
		$this->review->setOrganisation($organisation);

		$this->review->setReview($review);

		$this->review->setTimestamp(new \DateTime());

		/**
		 * if review exists update review
		 */
		$query = $this->reviewRepository->createQuery();
		$query->matching(
			$query->logicalAnd(
				$query->equals('userAccount', $userAccount),
				$query->equals('organisation', $organisation)
			)
		);
		$result = $query->execute();
		if($result->count() == 1) {
			$oldReview = $result->getFirst();
			$oldReview->setReview($review);
			$oldReview->setTimestamp(new \DateTime());
			$this->reviewRepository->update($oldReview);
			return "true";
		}

		/**
		 * else add review
		 */
		$this->reviewRepository->add($this->review);
		return "true";
	}

	/**
	 * @param \Project\Helpiez\Domain\Model\PendingOrganisation $pendingOrganisation
	 * @return string
	 */
	public function addOrganisationAction($pendingOrganisation) {
		$organisationName = $pendingOrganisation->getName();

		$query = $this->organisationRepository->createQuery();
		$query->matching(
			$query->equals('name', $organisationName)
		);
		$result = $query->execute();
		if($result->count() < 1) {
			return "false";
		}

		$query = $this->pendingOrganisationRepository->createQuery();
		$query->matching(
			$query->equals('name', $organisationName)
		);
		$result = $query->execute();
		if($result->count() < 1) {
			return "false";
		}

		$this->pendingOrganisationRepository->add($pendingOrganisation);
		return "true";
	}
}