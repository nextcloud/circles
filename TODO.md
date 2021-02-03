
###

- Verify that a user can be added to this circle (based on the Config of the circle)
- 



### Must be done when the project is more advanced:
Cannot be done until I get a better overview of the project

- [ ] #M004: confirm Member (hasMember() and member have an instance set) when managing RemoteEvent
- [ ] #E001: confirm and manage event when initiated from another instance.
- [ ] #C001: allow an 'All' request to the database only available to the backend to also includes some specific circles (single, fully hidden, ...)



### Lazy while coding:
I got lazy and afraid to get lost the first throw of code

- [ ] #M003: confirm other type of User when adding a new member to a circle.
- [ ] confirm MemberId and CircleId is really not known before creating entry in database.
- [x] Add better option to circles:manage:list
- [ ] when generating Single circle, update single_id with the generated id in the table circle_members


### Ideas
Some sparks that can happens anytime of the day or night.



### Questions
Should I do it ?


### renaming
Some Model/Method needs renaming for better readability

- [x] rename getViewer/setViewer to getInitiator/setInitiator
- [x] rename IMember to IFederatedUser
- [ ] rename Member to CircleMember
- [x] rename CurrentUser to FederatedUser
- [x] rename IRemoteEvent to IFederatedItem, IFederatedCommand, IFederatedAction, IFederatedObject
- [x] rename RemoteEvent to FederatedEvent





### Over the top

- [ ] #M002: When adding a member from a remote instance, request the remote instance to check user availability. Might be better to add an option to allow that check.




### Document
Because, you know...

